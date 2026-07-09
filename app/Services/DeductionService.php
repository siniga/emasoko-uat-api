<?php

namespace App\Services;

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
use App\Models\SmsLog;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DeductionService
{
    private $app_variable;
    protected MessagingService $messagingService;
    protected SmsTemplateService $smsTemplateService;

    public function __construct()
    {
        $this->messagingService = app(MessagingService::class);
        $this->smsTemplateService = app(SmsTemplateService::class);
    }

    protected function settlementPosting(): SettlementPostingService
    {
        return app(SettlementPostingService::class);
    }

    protected function externalCallback(): ExternalCallbackService
    {
        return app(ExternalCallbackService::class);
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
        $startDate = Carbon::parse($debitOrder->start_date);
        $installmentCount = (int) $debitOrder->installment_number;
        $dayOfMonth = $startDate->day;
        $lastInstallmentDate = $startDate->copy();

        for ($i = 2; $i <= $installmentCount; $i++) {
            $lastInstallmentDate = $this->advanceInstallmentDate(
                $lastInstallmentDate,
                (string) $debitOrder->periodicity,
                $dayOfMonth
            );
        }

        return $lastInstallmentDate->copy()->addYears(2)->toDateString();
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

                    // 1) Prefer OPEN + not created + due today.
                    $open = $debitOrder->installments()
                        ->where('installments.status', 'OPEN')
                        ->where('installments.created', 0)
                        ->whereDate('installments.payment_date', '<=', $current_date)
                        ->orderByRaw("
                            CASE 
                                WHEN DATE(installments.payment_date) = ? THEN 0
                                ELSE 1
                            END
                        ", [$current_date])
                        ->orderBy('installments.payment_date', 'desc')
                        ->first();

                    if ($open) {
                        $picked->push($open);
                    } else {
                        // 2) Fallback: earliest previous OPEN/FAILED/FUNDSCHECK installment.
                        $fallback = $debitOrder->installments()
                            ->whereIn('status', ['OPEN', 'FAILED', 'FUNDSCHECK'])
                            ->where(function ($query) {
                                $query->where('status', '!=', 'OPEN')
                                    ->orWhere('created', 0);
                            })
                            ->where(function ($query) {
                                $query->whereNull('remarks')
                                    ->orWhere('remarks', '!=', 'Account Is Valid');
                            })
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
    public function createInstallment(DebitOrder $debitOrder, bool $forceRegenerate = false)
    {
        $this->validateInstallmentGenerationInputs($debitOrder);

        return DB::transaction(function () use ($debitOrder, $forceRegenerate) {
            $debitOrder = DebitOrder::query()->lockForUpdate()->findOrFail($debitOrder->id);

            $existingInstallments = Installment::where('debit_order_id', $debitOrder->id)
                ->orderBy('installment_order')
                ->get();

            if ($existingInstallments->isNotEmpty()) {
                $hasOperationalHistory = $this->hasInstallmentOperationalHistory($debitOrder, $existingInstallments);

                if ($hasOperationalHistory && !$forceRegenerate) {
                    Log::warning('Skipping installment regeneration because operational history exists.', [
                        'debit_order_id' => $debitOrder->id,
                        'reference' => $debitOrder->reference,
                    ]);

                    return 200;
                }

                if ($hasOperationalHistory && $forceRegenerate) {
                    throw new Exception("Cannot force regenerate installments for mandate {$debitOrder->reference} because operational history already exists.");
                }
            }

            $scheduleRows = $this->buildInstallmentSchedule($debitOrder);
            $validOrders = [];

            foreach ($scheduleRows as $row) {
                $validOrders[] = $row['installment_order'];

                Installment::updateOrCreate(
                    [
                        'debit_order_id' => $debitOrder->id,
                        'installment_order' => $row['installment_order'],
                    ],
                    $row
                );
            }

            if ($existingInstallments->isNotEmpty()) {
                Installment::where('debit_order_id', $debitOrder->id)
                    ->whereNotIn('installment_order', $validOrders)
                    ->delete();
            }

            return 200;
        });
    }

    //create fixed installment
    protected function fixedInstallment(DebitOrder $debitOrder)
    {
        foreach ($this->buildFixedInstallmentRows($debitOrder) as $row) {
            Installment::updateOrCreate(
                ['debit_order_id' => $debitOrder->id, 'installment_order' => $row['installment_order']],
                $row
            );
        }
    }

    //create dynamic installment
    protected function dynamicInstallment(DebitOrder $debitOrder)
    {
        foreach ($this->buildDynamicInstallmentRows($debitOrder) as $row) {
            Installment::updateOrCreate(
                ['debit_order_id' => $debitOrder->id, 'installment_order' => $row['installment_order']],
                $row
            );
        }
    }

    protected function buildInstallmentSchedule(DebitOrder $debitOrder): array
    {
        return match (strtoupper((string) $debitOrder->debit_type)) {
            'FIXED' => $this->buildFixedInstallmentRows($debitOrder),
            'DYNAMIC' => $this->buildDynamicInstallmentRows($debitOrder),
            default => throw new Exception("Unsupported debit type [{$debitOrder->debit_type}] for mandate {$debitOrder->reference}."),
        };
    }

    protected function buildFixedInstallmentRows(DebitOrder $debitOrder): array
    {
        $periodicity = $debitOrder->periodicity;
        $startDate = Carbon::parse($debitOrder->start_date);
        $dayOfMonth = $startDate->day;
        $lastInstallmentDate = $startDate->copy();
        $installmentCount = (int) $debitOrder->installment_number;
        $totalAmount = (float) $debitOrder->total_amount;
        $installmentAmount = (float) $debitOrder->installment_amount;
        $sumInstallmentAmount = 0;
        $rows = [];

        for ($i = 1; $i <= $installmentCount; $i++) {
            if ($i === 1) {
                $installmentDate = $lastInstallmentDate->copy();
            } else {
                $installmentDate = strtoupper($periodicity) === 'MONTHLY'
                    ? $lastInstallmentDate->copy()->addMonthNoOverflow()->setDay(min($dayOfMonth, $lastInstallmentDate->copy()->addMonthNoOverflow()->daysInMonth))
                    : $this->advanceInstallmentDate($lastInstallmentDate, $periodicity, $dayOfMonth);
            }

            $sumInstallmentAmount += $installmentAmount;
            $scheduledAmount = ($sumInstallmentAmount > $totalAmount)
                ? $totalAmount - ($sumInstallmentAmount - $installmentAmount)
                : $installmentAmount;

            $lastInstallmentDate = $installmentDate->copy();
            $rows[] = $this->buildInstallmentAttributes($i, $scheduledAmount, $installmentDate);
        }

        return $rows;
    }

    protected function buildDynamicInstallmentRows(DebitOrder $debitOrder): array
    {
        $periodicity = $debitOrder->periodicity;
        $startDate = Carbon::parse($debitOrder->start_date);
        $installmentCount = (int) $debitOrder->installment_number;
        $totalAmount = (float) $debitOrder->total_amount;
        $maxInstallmentAmount = (float) $debitOrder->max_installment_amount;
        $minInstallmentAmount = (float) $debitOrder->min_installment_amount;
        $dayOfMonth = $startDate->day;
        $lastInstallmentDate = $startDate->copy();
        $sumInstallmentAmount = 0;
        $rows = [];

        for ($i = 1; $i <= $installmentCount; $i++) {
            $installmentDate = $i === 1
                ? $lastInstallmentDate->copy()
                : $this->advanceInstallmentDate($lastInstallmentDate, $periodicity, $dayOfMonth);

            $sumInstallmentAmount += $maxInstallmentAmount;
            $scheduledAmount = ($sumInstallmentAmount > $totalAmount)
                ? $totalAmount - ($sumInstallmentAmount - $maxInstallmentAmount)
                : $maxInstallmentAmount;

            $lastInstallmentDate = $installmentDate->copy();

            $rows[] = $this->buildInstallmentAttributes(
                $i,
                $scheduledAmount,
                $installmentDate,
                min($minInstallmentAmount, $scheduledAmount),
                $scheduledAmount
            );
        }

        return $rows;
    }

    protected function buildInstallmentAttributes(
        int $order,
        float $amount,
        Carbon $paymentDate,
        ?float $minInstallmentAmount = null,
        ?float $maxInstallmentAmount = null
    ): array {
        return [
            'installment_order' => $order,
            'min_installment_amount' => $minInstallmentAmount,
            'max_installment_amount' => $maxInstallmentAmount,
            'installment_amount' => $amount,
            'current_balance' => 0,
            'outstanding_amount' => $amount,
            'payment_date' => $paymentDate->toDateString(),
            'status' => 'OPEN',
            'remarks' => null,
            'effective_payment_date' => null,
            'payment_reference' => null,
            'created' => 0,
        ];
    }

    protected function validateInstallmentGenerationInputs(DebitOrder $debitOrder): void
    {
        if (!$debitOrder->start_date) {
            throw new Exception("Mandate {$debitOrder->reference} is missing start date.");
        }

        if ((int) $debitOrder->installment_number <= 0) {
            throw new Exception("Mandate {$debitOrder->reference} has invalid installment count.");
        }

        if ((float) $debitOrder->total_amount <= 0) {
            throw new Exception("Mandate {$debitOrder->reference} has invalid total amount.");
        }

        if (strtoupper((string) $debitOrder->debit_type) === 'FIXED' && (float) $debitOrder->installment_amount <= 0) {
            throw new Exception("Mandate {$debitOrder->reference} has invalid fixed installment amount.");
        }

        if (strtoupper((string) $debitOrder->debit_type) === 'DYNAMIC') {
            if ((float) $debitOrder->max_installment_amount <= 0) {
                throw new Exception("Mandate {$debitOrder->reference} has invalid maximum installment amount.");
            }

            if ((float) $debitOrder->min_installment_amount > (float) $debitOrder->max_installment_amount) {
                throw new Exception("Mandate {$debitOrder->reference} has minimum installment amount greater than maximum.");
            }
        }
    }

    protected function hasInstallmentOperationalHistory(DebitOrder $debitOrder, Collection $existingInstallments): bool
    {
        $installmentIds = $existingInstallments->pluck('id')->filter()->values();

        if ($installmentIds->isEmpty()) {
            return false;
        }

        if (CollectionDebitOrder::where('debit_order_id', $debitOrder->id)->exists()) {
            return true;
        }

        if (PaymentSettlement::where('debit_order_id', $debitOrder->id)->exists()) {
            return true;
        }

        if (InstallmentPayment::whereIn('installment_id', $installmentIds)->exists()) {
            return true;
        }

        return $existingInstallments->contains(function ($installment) {
            return strtoupper((string) ($installment->status ?? 'OPEN')) !== 'OPEN'
                || (float) ($installment->current_balance ?? 0) > 0
                || !is_null($installment->effective_payment_date)
                || !is_null($installment->payment_reference)
                || (int) ($installment->created ?? 0) === 1;
        });
    }

    protected function advanceInstallmentDate(Carbon $currentDate, string $periodicity, int $dayOfMonth): Carbon
    {
        $periodicity = $this->normalizePeriodicity($periodicity);
        $date = $currentDate->copy();

        switch ($periodicity) {
            case 'ANNUALLY':
            case 'YEARLY':
                return $date->addYear();
            case 'QUARTERLY':
                $date->addMonthsNoOverflow(3);
                $date->day = min($dayOfMonth, $date->daysInMonth);
                return $date;
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

    protected function normalizePeriodicity(string $periodicity): string
    {
        return match (strtoupper(trim($periodicity))) {
            'DAILY' => 'DAILY',
            'WEEKLY' => 'WEEKLY',
            'MONTHLY' => 'MONTHLY',
            'QUARTELY', 'QUARTERLY' => 'QUARTERLY',
            'ANNUALLY', 'YEARLY' => 'YEARLY',
            default => 'MONTHLY',
        };
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
                    Log::info('Collection status row skipped: bank response already processed.', [
                        'batch_id' => $batch->id,
                        'collection_reference' => $batch->collectionReference,
                        'installment_id' => $installment->id,
                        'line_id' => $line->id,
                        'response_hash' => $responseHash,
                    ]);
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
                    if (in_array($status, ["FAIL", "FAILED", "FUNDSCHECK"], true)) {
                        $normalizedStatus = $status == "FUNDSCHECK"
                            ? PaymentSettlement::STATUS_FUNDSCHECK
                            : PaymentSettlement::STATUS_FAILED;

                        $paymentReference = "COL-{$batch->collectionReference}-{$installment->id}";
                        $existingSettlement = $this->existingSettlement('NMB', $paymentReference, $installment);

                        $settlement = $this->settlementPosting()->post([
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
                            'payment_reference' => $paymentReference,
                            'raw_payload_hash' => $responseHash,
                        ]);

                        $this->dispatchCollectionCallback($settlement, $this->shouldDispatchCollectionCallback($existingSettlement, $normalizedStatus));

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

                        $paymentReference = "COL-{$batch->collectionReference}-{$installment->id}";
                        $existingSettlement = $this->existingSettlement('NMB', $paymentReference, $installment);

                        $settlement = $this->settlementPosting()->post([
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
                            'payment_reference' => $paymentReference,
                            'raw_payload_hash' => $responseHash,
                        ]);

                        $this->dispatchCollectionCallback($settlement, $this->shouldDispatchCollectionCallback($existingSettlement, $normalizedStatus));

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
                    Log::info('Failed collection status row skipped: bank response already processed.', [
                        'batch_id' => $batch->id,
                        'collection_reference' => $batch->collectionReference,
                        'installment_id' => $installment->id,
                        'line_id' => $line->id,
                        'response_hash' => $responseHash,
                    ]);
                    continue;
                }

                //check if installment status != CLOSED
                if ($installment->status != "CLOSED") {
                    //status and paid amount
                    $status = strtoupper($fields[7] ?? 'FAILED');
                    $message = ucwords(strtolower($fields[8]));
                    $normalizedStatus = $status === 'FUNDSCHECK'
                        ? PaymentSettlement::STATUS_FUNDSCHECK
                        : PaymentSettlement::STATUS_FAILED;

                    $paymentReference = "COL-{$batch->collectionReference}-{$installment->id}";
                    $existingSettlement = $this->existingSettlement('NMB', $paymentReference, $installment);

                    $settlement = $this->settlementPosting()->post([
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
                        'payment_reference' => $paymentReference,
                        'raw_payload_hash' => $responseHash,
                    ]);

                    $this->dispatchCollectionCallback($settlement, $this->shouldDispatchCollectionCallback($existingSettlement, $normalizedStatus));

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
                $accountLastDigits = $this->smsTemplateService->accountLastDigits($debitOrder->customerAccount?->account_number);
                $templateCode = strtoupper((string) $debitOrder->channel) === 'CRDB' && (float) $outstanding_amount > 0
                    ? 'partial_deduction'
                    : 'deduction_success';
                $fallback = "Makato ya tarehe {$payment_date} ya kiasi cha shilingi {$paidAmt} yamekamilishwa kwa {$businessName}. Kiasi kilichobaki ni Tsh {$outstandingAmt}.";
                $message = $this->smsTemplateService->render($templateCode, $debitOrder->channel, [
                    'payment_date' => $payment_date,
                    'amount' => $paidAmt,
                    'service_provider' => $businessName,
                    'outstanding_amount' => $outstandingAmt,
                    'account_last_digits' => $accountLastDigits,
                    'mandate_reference' => $debitOrder->reference,
                ], $fallback);

                //message id
                $messageId = $this->messagingService->generateMessageId();

                //save sms to database
                $sms_log = SmsLog::firstOrNew([
                    'message_id' => $messageId,
                    'phone' => $this->messagingService->castPhone(trim($phone)),
                ]);
                $sms_log->uuid = Str::uuid();
                $sms_log->debit_order_id = $debitOrder->id;
                $sms_log->message = $message;
                $sms_log->number_of_sms = $this->messagingService->countNumberOfSms($message);
                $sms_log->status = 'PENDING';
                $sms_log->created_by = Auth::user()->id;
                $sms_log->save();

                //trigger background job for send SMS
                dispatch(new SendSMSJob(['messageId' => $messageId]));

                $this->externalCallback()->dispatchCollectionStatus($installment, 'COLLECTION_PAID');
            }
        }

        return 200;
    }

    protected function existingSettlement(string $channel, string $paymentReference, Installment $installment): ?PaymentSettlement
    {
        return PaymentSettlement::where([
            'channel' => $channel,
            'payment_reference' => $paymentReference,
            'installment_id' => $installment->id,
        ])->first();
    }

    protected function shouldDispatchCollectionCallback(?PaymentSettlement $existingSettlement, string $normalizedStatus): bool
    {
        return !$existingSettlement || strtoupper((string) $existingSettlement->normalized_status) !== strtoupper($normalizedStatus);
    }

    protected function dispatchCollectionCallback(PaymentSettlement $settlement, bool $shouldDispatch = true): void
    {
        if (!$shouldDispatch) {
            Log::info('External collection callback skipped: settlement status did not change.', [
                'settlement_id' => $settlement->id,
                'installment_id' => $settlement->installment_id,
                'payment_reference' => $settlement->payment_reference,
                'normalized_status' => $settlement->normalized_status,
            ]);
            return;
        }

        if (!$settlement->installment_id) {
            Log::warning('External collection callback skipped: settlement has no installment.', [
                'settlement_id' => $settlement->id,
                'payment_reference' => $settlement->payment_reference,
                'normalized_status' => $settlement->normalized_status,
            ]);
            return;
        }

        $settlement->loadMissing('installment.debitOrder.partner');

        if (!$settlement->installment) {
            Log::warning('External collection callback skipped: settlement installment could not be loaded.', [
                'settlement_id' => $settlement->id,
                'installment_id' => $settlement->installment_id,
                'payment_reference' => $settlement->payment_reference,
            ]);
            return;
        }

        $eventType = match (strtoupper((string) $settlement->normalized_status)) {
            PaymentSettlement::STATUS_SUCCESS => 'COLLECTION_PAID',
            PaymentSettlement::STATUS_PARTIAL => 'COLLECTION_PARTIAL_PAID',
            PaymentSettlement::STATUS_FUNDSCHECK => 'COLLECTION_FUNDSCHECK',
            PaymentSettlement::STATUS_UNALLOCATED => 'COLLECTION_UNALLOCATED',
            default => 'COLLECTION_FAILED',
        };

        $this->externalCallback()->dispatchCollectionStatus($settlement->installment, $eventType, [
            'channel' => $settlement->channel,
            'settlement_reference' => $settlement->payment_reference,
            'external_reference' => $settlement->external_reference,
            'bank_status' => $settlement->raw_status,
            'settlement_status' => $settlement->normalized_status,
            'paid_amount' => (float) $settlement->paid_amount,
            'transaction_at' => $settlement->transaction_at
                ? Carbon::parse($settlement->transaction_at)->toIso8601String()
                : null,
        ]);
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
