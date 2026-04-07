<?php

namespace App\Services;

use App\Http\Resources\InstallmentResource;
use App\Jobs\ApiCallbackJob;
use App\Jobs\SendSMSJob;
use App\Models\AppVariable;
use App\Models\CollectionBatch;
use App\Models\CollectionDebitOrder;
use App\Models\DebitOrder;
use App\Models\Partner;
use App\Models\PartnerAccount;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Models\PaymentSettlement;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class DeductionService
{
    private $app_variable;
    public function __construct() {}

    protected function settlementPosting(): SettlementPostingService
    {
        return app(SettlementPostingService::class);
    }

    protected function appVariable(): AppVariable
    {
        if (!$this->app_variable) {
            $this->app_variable = AppVariable::first();
        }

        if (!$this->app_variable) {
            throw new Exception('App variables are not initialized');
        }

        return $this->app_variable;
    }

    // check if customer account has active debit order
    public function hasActiveDebitOrder($customer_account_uuid)
    {
        //declare array
        $arr_debit_order = [];

        // debit order
        $debit_order = DebitOrder::whereHas("customerAccount", function ($q) use ($customer_account_uuid) {
            $q->where('customer_accounts.uuid', $customer_account_uuid);
        })->where('debit_orders.contract_status', 'OPEN');

        if ($debit_order->exists()) {
            foreach ($debit_order->get() as $debit_order) {
                $arr_debit_order[] = [
                    'id' => $debit_order->id,
                    'uuid' => $debit_order->uuid,
                    'created_date' => $debit_order->created_at,
                    'start_date' => $debit_order->start_date,
                    'end_date' => $debit_order->end_date,
                    'total_amount' => $debit_order->total_amount,
                    'installment_amount' => ($debit_order->debit_type == "FIXED") ? $debit_order->installment_amount : $debit_order->max_installment_amount,
                    'paid_amount' => $debit_order->paid_amount,
                    'outstanding_amount' => $debit_order->outstanding_amount,
                    'number_of_installments' => $debit_order->installment_number,
                    'paid_installments' => $debit_order->paid_installment,
                    'failed_installments' => $debit_order->failed_installment,
                ];
            }
        }

        return $arr_debit_order;
    }

    //create debit order evidence of aggrement
    public function createEvidence(DebitOrder $debitOrder) {}

    //calculate number of installment
    public function calculateNumberOfInstallments($debit_type, $total_amount, $iamount, $max_amount)
    {
        $numberInstallment = 0;

        if ($debit_type == "FIXED") {
            $numberInstallment = floatval($total_amount) / floatval($iamount);
            return ceil($numberInstallment); //$numberInstallment;
        } else if ($debit_type == "DYNAMIC") {
            $numberInstallment = floatval($total_amount) / floatval($max_amount);
            return ceil($numberInstallment); //$numberInstallment;
        }
    }

    // calculate debit end date
    public function calculateEndDate(DebitOrder $debitOrder)
    {
        //1. Get debit order details
        $periodicity = $debitOrder->periodicity;
        $start_date = $debitOrder->start_date;

        $last_installment_date = null;
        $number_of_installment = $debitOrder->installment_number;

        //dates
        $dayOfMonth = date('d', strtotime($start_date));

        //TODO:Check for each channel 

        // 2. Set addition number of days from periodicity
        $number_of_days = "+0 day";
        if ($periodicity == "ANNUALLY")
            $number_of_days = "+1 year";
        if ($periodicity == "MONTHLY")
            $number_of_days = '+30 day';
        else if ($periodicity == "WEEKLY")
            $number_of_days = '+1 week';
        else if ($periodicity == "DAILY")
            $number_of_days = "+1 day";


        for ($i = 1; $i <= $number_of_installment; $i++) {
            if ($last_installment_date == null) {
                $installment_date = date('Y-m-d', strtotime($start_date));

                //assign last installment date
                $last_installment_date =  $installment_date;
            } else {
                $installment_date = date('Y-m-d', strtotime($last_installment_date . $number_of_days));

                //formating all the dates if periodicity = "MONTHLY"
                if ($periodicity == "MONTHLY") {
                    $year = date('Y', strtotime($installment_date));
                    $month = date('m', strtotime($installment_date));

                    $date = Carbon::createFromDate($year, $month, $dayOfMonth);
                    $installment_date = $date->format('Y-m-d'); // Format as dd-mm-yyyy
                }

                //assign last installment date
                $last_installment_date =  $installment_date;
            }
        }

        //add buffer of 2 year
        $debit_end_date = date('Y-m-d', strtotime($last_installment_date . " +2 year"));

        return $debit_end_date;
    }

    //create new collection date
    public function createCollectionDate($payment_date)
    {
        //original date
        $original = Carbon::parse($payment_date);

        // Get current month and year
        $currentMonth = now()->month;

        // Adjust year if original was in December and we're now in January
        $newYear = ($original->month === 12 && $currentMonth === 1) ? $original->year + 1 : $original->year;

        // Try to build new date with current month, original day and adjusted year
        try {
            $newDate = Carbon::create($newYear, $currentMonth, $original->day);
        } catch (\Exception $e) {
            // Day is invalid for the current month (e.g., Feb 30)
            $newDate = Carbon::create($newYear, $currentMonth, 1)->endOfMonth();
        }

        return $newDate;
    }

    // get open and failed installments
    public function getInstallments(Partner $partner, $current_date, ?int $limit = null): Collection
    {
        $picked = collect();

        // Stream debit orders in chunks to avoid loading everything at once
        DebitOrder::query()
            ->eligibleForCollection('NMB')
            ->whereDate('next_debit_date', $current_date)
            ->whereHas('partner', function ($q) use ($partner) {
                $q->where('partners.id', $partner->id);
            })
            ->orderBy('id') // required for chunkById determinism
            ->chunkById(200, function ($orders) use (&$picked, $current_date, $limit) {
                foreach ($orders as $debitOrder) {
                    // If a limit was provided and we've reached it, stop early.
                    if ($limit !== null && $picked->count() >= $limit) {
                        return false; // abort further chunking
                    }

                    // 1) Prefer OPEN + not created + due today (earliest first)
                    $open = $debitOrder->installments()
                        ->where('installments.status', 'OPEN')
                        ->where('installments.created', 0)
                        ->whereDate('installments.payment_date', $current_date)
                        ->orderBy('installments.payment_date')
                        ->first();

                    if ($open) {
                        $picked->push($open);
                    } else {
                        // 2) Fallback: earliest FAILED/FUNDSCHECK before today, exclude "Account Is Valid"
                        $fallback = $debitOrder->installments()
                            ->whereIn('status', ['FAILED', 'FUNDSCHECK'])
                            ->where('remarks', '!=', 'Account Is Valid')
                            ->whereDate('payment_date', '<', $current_date)
                            ->orderBy('installments.payment_date')
                            ->first();

                        if ($fallback) {
                            $picked->push($fallback);
                        }
                    }
                }
            });

        // If limit is set, trim; otherwise return everything collected.
        return $limit !== null ? $picked->take($limit)->values() : $picked->values();
    }

    //create debit order installments
    public function createInstallment(DebitOrder $debitOrder)
    {
        //1. Get debit order details
        $debit_type = $debitOrder->debit_type;

        //3. Delete all installments
        $deleted_installment = Installment::where('debit_order_id', $debitOrder->id)->delete();

        //4. Create FIXED and DYNAMIC installments
        if ($debit_type == "FIXED")
            $this->fixedInstallment($debitOrder);
        else if ($debit_type == "DYNAMIC")
            $this->dynamicInstallment($debitOrder);

        return 200;
    }

    //create fixed installment
    protected function fixedInstallment(DebitOrder $debitOrder)
    {
        $periodicity = $debitOrder->periodicity;
        $startDate = Carbon::parse($debitOrder->start_date);
        $dayOfMonth = $startDate->day;

        $lastInstallmentDate = $startDate->copy();
        $installmentCount = $debitOrder->installment_number;
        $totalAmount = $debitOrder->total_amount;
        $installmentAmount = $debitOrder->installment_amount;

        $sumInstallmentAmount = 0;

        for ($i = 1; $i <= $installmentCount; $i++) {
            if ($i === 1) {
                $installmentDate = $lastInstallmentDate->copy();
            } else {
                if ($periodicity === "MONTHLY") {
                    $installmentDate = $lastInstallmentDate->copy()->addMonthNoOverflow();
                    // Set same day, fallback to last valid day of month
                    $installmentDate->day = min($dayOfMonth, $installmentDate->daysInMonth);
                } else {
                    $installmentDate = $this->advanceInstallmentDate($lastInstallmentDate, $periodicity, $dayOfMonth);
                }
            }

            $sumInstallmentAmount += $installmentAmount;

            // Calculate paid amount safely
            $paidAmount = ($sumInstallmentAmount > $totalAmount)
                ? $totalAmount - ($sumInstallmentAmount - $installmentAmount)
                : $installmentAmount;

            // Save for next iteration
            $lastInstallmentDate = $installmentDate->copy();

            // Create or update installment
            Installment::updateOrCreate(
                ['debit_order_id' => $debitOrder->id, 'installment_order' => $i],
                [
                    'installment_amount' => $paidAmount,
                    'current_balance' => 0,
                    'outstanding_amount' => $paidAmount,
                    'payment_date' => $installmentDate->toDateString()
                ]
            );
        }
    }
    //create dynamic installment
    protected function dynamicInstallment(DebitOrder $debitOrder)
    {
        $periodicity = $debitOrder->periodicity;
        $startDate = Carbon::parse($debitOrder->start_date);
        $number_of_installment = $debitOrder->installment_number;
        $total_amount = $debitOrder->total_amount;

        $dayOfMonth = $startDate->day;
        $lastInstallmentDate = $startDate->copy();

        $sum_installment_amount = 0;
        for ($i = 1; $i <= $number_of_installment; $i++) {
            if ($i === 1) {
                $installmentDate = $lastInstallmentDate->copy();
                $sum_installment_amount += $debitOrder->max_installment_amount;
                $paid_amount = $debitOrder->max_installment_amount;
            } else {
                $installmentDate = $this->advanceInstallmentDate($lastInstallmentDate, $periodicity, $dayOfMonth);

                //SUMMATION installment Amount
                $sum_installment_amount += $debitOrder->max_installment_amount;

                //PAID Amount
                if ($sum_installment_amount > $total_amount)
                    $paid_amount = $total_amount - ($sum_installment_amount - $debitOrder->max_installment_amount);
                else
                    $paid_amount = $debitOrder->max_installment_amount;
            }

            //assign last installment date
            $lastInstallmentDate = $installmentDate->copy();

            //check for paid amount and min 
            $min_paid_amount = $debitOrder->min_installment_amount;

            if ($min_paid_amount > $paid_amount)
                $min_paid_amount = $paid_amount;

            //3. Create Installment deduction
            $installment = Installment::firstOrNew(
                ['debit_order_id' => $debitOrder->id, 'installment_order' => $i],
                [
                    'min_installment_amount' => $min_paid_amount,
                    'max_installment_amount' => $paid_amount,
                    'installment_amount' => $paid_amount,
                    'current_balance' => 0,
                    'outstanding_amount' => $paid_amount,
                    'payment_date' => $installmentDate->toDateString()
                ]
            );
            $installment->save();
        }
    }

    protected function advanceInstallmentDate(Carbon $currentDate, string $periodicity, int $dayOfMonth): Carbon
    {
        $periodicity = strtoupper($periodicity);
        $date = $currentDate->copy();

        switch ($periodicity) {
            case 'ANNUALLY':
            case 'YEARLY':
                return $date->addYear();
            case 'WEEKLY':
                return $date->addWeek();
            case 'DAILY':
                return $date->addDay();
            case 'MONTHLY':
            default:
                $date->addMonthNoOverflow();
                $date->day = min($dayOfMonth, $date->daysInMonth);
                return $date;
        }
    }

    public function updateInstallments(CollectionBatch $batch, $data)
    {
        //Convert the data into an array of rows
        $rows = explode("\n", $data);

        //remove empty rows
        $rows = array_filter($rows);

        //count success and failed installments
        $success_installments = 0;
        $failed_installments = 0;

        //2. process installment
        foreach ($rows as $index => $row) {
            // Skip the first row as it contains headers
            if ($index === 0) {
                continue;
            }

            // Split the row into individual fields using semicolon as delimiter
            $fields = explode(";", $row);

            //get installment from id
            $installment = Installment::where('id', '=', $fields[0])->first();

            //check if installment exists AND channel == "NMB"
            if ($installment && $installment->debitOrder->channel == "NMB") {
                $line = $this->resolveCollectionLine($batch, $installment);
                $responseHash = md5(trim($row));

                if ($line && $line->response_hash === $responseHash && $line->processed_at) {
                    continue;
                }

                //check if installment status != CLOSED
                if ($installment->status != "CLOSED") {
                    //installment amount
                    $installment_amount = $installment->installment_amount;

                    //status and paid amount
                    $message = ucwords(strtolower($fields[8]));
                    $status = strtoupper($fields[7]);
                    $amount = floatval($fields[6]);

                    //check for status
                    if ($status == "FAIL" || $status == "FUNDSCHECK") {
                        $normalizedStatus = $status == "FUNDSCHECK"
                            ? PaymentSettlement::STATUS_FUNDSCHECK
                            : PaymentSettlement::STATUS_FAILED;

                        $this->settlementPosting()->post([
                            'channel' => 'NMB',
                            'batch' => $batch,
                            'line' => $line,
                            'debit_order' => $installment->debitOrder,
                            'installment' => $installment,
                            'amount' => 0,
                            'raw_status' => $status,
                            'normalized_status' => $normalizedStatus,
                            'remarks' => $message,
                            'effective_payment_date' => $installment->effective_payment_date ?: now()->toDateString(),
                            'payment_reference' => "COL-{$batch->collectionReference}-{$installment->id}",
                            'raw_payload_hash' => $responseHash,
                        ]);

                        //increment failed installments
                        $failed_installments += 1;

                        if ($message == 'Insufficient Funds') {
                            //format amount
                            $installmentAmt = number_format($installment_amount);

                            //payment date
                            $paymentDate = $installment->effective_payment_date;

                            //TODO: Messaging Module 
                            //1.0: Send Single email to service provider => show all failed collections

                        }
                    } else if ($status == "SUCCESS") {
                        $openingBalance = (float) ($installment->outstanding_amount ?? 0);
                        $normalizedStatus = $amount > 0 && $amount < $openingBalance
                            ? PaymentSettlement::STATUS_PARTIAL
                            : PaymentSettlement::STATUS_SUCCESS;

                        $this->settlementPosting()->post([
                            'channel' => 'NMB',
                            'batch' => $batch,
                            'line' => $line,
                            'debit_order' => $installment->debitOrder,
                            'installment' => $installment,
                            'amount' => $amount,
                            'raw_status' => $status,
                            'normalized_status' => $normalizedStatus,
                            'remarks' => $message,
                            'effective_payment_date' => $installment->effective_payment_date ?: date('Y-m-d'),
                            'payment_reference' => "COL-{$batch->collectionReference}-{$installment->id}",
                            'raw_payload_hash' => $responseHash,
                        ]);

                        //increment success installments
                        $success_installments += 1;
                    } else {
                        //updated status of installment
                        $installment->status = $status;
                        $installment->save();

                        if ($line) {
                            $line->status = $status;
                            $line->bank_status = $status;
                            $line->bank_message = $message;
                            $line->response_hash = $responseHash;
                            $line->processed_at = now();
                            $line->save();
                        }
                    }
                }
            }
        }

        return [$success_installments, $failed_installments];
    }

    public function updateFailedInstallments(CollectionBatch $batch, $data)
    {
        //Convert the data into an array of rows
        $rows = explode("\n", $data);

        //remove empty rows
        $rows = array_filter($rows);

        //count success and failed installments
        $failed_installments = 0;

        //2. process installment
        foreach ($rows as $index => $row) {
            // Skip the first row as it contains headers
            if ($index === 0) {
                continue;
            }

            // Split the row into individual fields using semicolon as delimiter
            $fields = explode(";", $row);

            //get installment from id
            $installment = Installment::where('id', '=', $fields[0])->first();

            //check if installment exists AND channel == "NMB"
            if ($installment && $installment->debitOrder->channel == "NMB") {
                $line = $this->resolveCollectionLine($batch, $installment);
                $responseHash = md5(trim($row));

                if ($line && $line->response_hash === $responseHash && $line->processed_at) {
                    continue;
                }

                //check if installment status != CLOSED
                if ($installment->status != "CLOSED") {
                    //status and paid amount
                    $message = ucwords(strtolower($fields[8]));

                    $this->settlementPosting()->post([
                        'channel' => 'NMB',
                        'batch' => $batch,
                        'line' => $line,
                        'debit_order' => $installment->debitOrder,
                        'installment' => $installment,
                        'amount' => 0,
                        'raw_status' => 'FAILED',
                        'normalized_status' => PaymentSettlement::STATUS_FAILED,
                        'remarks' => $message,
                        'effective_payment_date' => $installment->effective_payment_date ?: now()->toDateString(),
                        'payment_reference' => "COL-{$batch->collectionReference}-{$installment->id}",
                        'raw_payload_hash' => $responseHash,
                    ]);

                    //increment failed installments
                    $failed_installments += 1;
                }
            }
        }

        return [$failed_installments];
    }

    //process installment
    public function processInstallment(Installment $installment, ?CollectionDebitOrder $line = null)
    {
        // Legacy entry point. New settlement posting should go through SettlementPostingService.
        //installment status
        $installment_status = strtoupper($installment->status);
        $amount = $installment->last_paid_amount; //paid amount

        //0. Check if installment status is not CLOSED
        if ($installment_status == 'OPEN' || $installment_status == 'FAILED' || $installment_status == 'FUNDSCHECK' || $installment_status == 'PARTIAL_PAID') {
            //check if installment already updated
            $outstanding_amount = $installment->outstanding_amount;

            if ($outstanding_amount <= 0) {
                //2. update installment status {CLOSED}
                $installment->status = "CLOSED";
                $installment->save();
            } else {
                //1. Update installment => outstanding amount, current balance
                $outstanding_amount = $installment->outstanding_amount - $amount;
                $current_balance = $installment->current_balance + $amount;
                $installment->outstanding_amount = $outstanding_amount;
                $installment->current_balance = $current_balance;

                //2. update installment status {OPEN, CLOSED, PARTIAL_PAID}
                if ($outstanding_amount <= 0)
                    $installment->status = "CLOSED";
                else if ($outstanding_amount > 0)
                    $installment->status = "PARTIAL_PAID";

                //3. Save installment
                $installment->save();

                //5. Update debit order => outstanding amount, current balance
                $debitOrder = $installment->debitOrder;
                $outstanding_amount = $debitOrder->outstanding_amount - $amount;
                $current_balance = $debitOrder->current_balance + $amount;
                $debitOrder->outstanding_amount = $outstanding_amount;
                $debitOrder->current_balance = $current_balance;
                if (isset($debitOrder->paid_amount)) {
                    $debitOrder->paid_amount = ($debitOrder->paid_amount ?? 0) + $amount;
                }

                //6. Update contract status = outstanding amount <= 0
                if ($outstanding_amount <= 0)
                    $debitOrder->contract_status = "CLOSED";

                //save debit order
                $debitOrder->save();
                $this->updateNextDebitDate($debitOrder);

                //installment amount
                $paid_amount = $installment->last_paid_amount;

                //payment date
                $payment_date = $installment->payment_date;

                //format amount
                $outstandingAmt = number_format($outstanding_amount);
                $paidAmt = number_format($paid_amount);

                //business name
                $businessName = $debitOrder->partner->business_name;

                //send message for outstanding amount and current balance from eMakato.
                $phone = $installment->debitOrder->customerAccount->phone;
                $message = "Makato ya tarehe {$payment_date} ya kiasi cha shilingi {$paidAmt} yamekamilishwa kwa {$businessName}. Kiasi kilichobaki ni Tsh {$outstandingAmt}.";

                //Send SMS
                $data = ["phone" => $phone, "message" => $message];
                dispatch(new SendSMSJob($data));

                if ($debitOrder->partner->callback_url && $debitOrder->partner->callback_url != "") {
                    //dispatch callback job
                    $callback_data = [
                        'callback_type' => "COLLECTION_STATUS",
                        'callback_url' => $debitOrder->partner->callback_url,
                        'collection' => InstallmentResource::collection($installment),
                    ];

                    // call CollectionCallbackJob
                    dispatch(new ApiCallbackJob($callback_data));
                }
            }
        } else if ($installment->status == 'CLOSED') {
            Log::debug("Installement => Closed");
        }

        return 200;
    }

    protected function resolveCollectionLine(CollectionBatch $batch, Installment $installment): ?CollectionDebitOrder
    {
        return CollectionDebitOrder::where('collection_batch_id', $batch->id)
            ->where('installment_id', $installment->id)
            ->latest('id')
            ->first();
    }

    public function syncNextDebitDate(DebitOrder $debitOrder, ?Carbon $referenceDate = null): ?string
    {
        try {
            $referenceDate = $referenceDate ?: now();
            $startOfMonth = $referenceDate->copy()->startOfMonth()->toDateString();

            $currentCycleInstallment = $debitOrder->installments()
                ->where('status', '!=', 'CLOSED')
                ->whereDate('payment_date', '>=', $startOfMonth)
                ->orderBy('payment_date', 'asc')
                ->first();

            $nextInstallment = $currentCycleInstallment ?: $debitOrder->installments()
                ->where('status', '!=', 'CLOSED')
                ->orderBy('payment_date', 'asc')
                ->first();

            $debitOrder = DebitOrder::findOrFail($debitOrder->id);

            if ($nextInstallment) {
                $debitOrder->next_debit_date = Carbon::parse($nextInstallment->payment_date)->toDateString();
            } else {
                $debitOrder->next_debit_date = null;
                $debitOrder->contract_status = 'CLOSED';
            }

            $debitOrder->save();

            return $debitOrder->next_debit_date;
        } catch (Exception $e) {
            Log::debug("Invalid date format: " . $e->getMessage());
            return null;
        }
    }

    //update debit date
    public function updateNextDebitDate(DebitOrder $debitOrder)
    {
        return $this->syncNextDebitDate($debitOrder);
    }

    //get installment amount from debit type
    public function getInstallmentAmount(DebitOrder $debitOrder)
    {
        if ($debitOrder->debit_type == "FIXED")
            return $debitOrder->installment_amount;
        else if ($debitOrder->debit_type == "DYNAMIC")
            return $debitOrder->max_installment_amount;
        else
            return $debitOrder->installment_amount;
    }

    //get last installment order
    public function getLastInstallmentOrder(DebitOrder $debitOrder)
    {
        $last = $debitOrder->installments()->orderBy('created_at', 'desc')->first();

        if ($last)
            return $last->installment_order;
        else
            return 0;
    }

    //get mandate reference
    public function getMandateReference()
    {
        $appVariable = $this->appVariable();

        $cur_mandate_reference = ((int) $appVariable->cur_mandate_reference) + 1;

        //update app variable
        $appVariable->cur_mandate_reference = $cur_mandate_reference;
        $appVariable->save();

        return $cur_mandate_reference;
    }

    //get collection reference
    public function getCollectionReference()
    {
        $appVariable = $this->appVariable();

        $cur_collection_reference = ((int) $appVariable->cur_collection_reference) + 1;

        //update app variable
        $appVariable->cur_collection_reference = $cur_collection_reference;
        $appVariable->save();

        return $cur_collection_reference;
    }

    //generate random alphanumeric string
    public static function generateRandomString(int $length = 10): string
    {
        $pool = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $pool[random_int(0, strlen($pool) - 1)];
        }
        return $out;
    }

    //generate unique payment reference
    function uniquePaymentReference(int $length = 10): string
    {
        do {
            $code = Str::upper($this->generateRandomString($length));
        } while (DB::table("installments")->where("payment_reference", $code)->exists());
        return $code;
    }

    //check for duplicate

    //check if mandate end date pass

    //check if collected this month
}
