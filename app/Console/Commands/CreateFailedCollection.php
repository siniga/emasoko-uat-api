<?php

namespace App\Console\Commands;

use App\Jobs\PushCollectionJob;
use App\Jobs\SendSMSJob;
use App\Models\CollectionBatch;
use App\Models\CollectionDebitOrder;
use App\Models\Installment;
use App\Models\Partner;
use App\Models\DebitOrder;
use App\Services\BankPayloadService;
use App\Services\BankApiService;
use App\Services\DeductionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateFailedCollection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:failed-collection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create failed collection';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //current month
        $current_month = now()->month;
        Log::debug("== Current Month: '{$current_month}' ==");

        //initialize deduction service
        $deductionService = new DeductionService();

        //collection references
        $arr_references = [10529, ];
        $collection_batches = CollectionBatch::whereIn('collectionReference', $arr_references)->orderBy('collectionReference', 'ASC');

        if ($collection_batches->count() > 0) {
            //take the first 10 collection batches
            $collection_batches = $collection_batches->get();

            foreach ($collection_batches as $collection_batch) {
                // validate the date before creating new collection dates
                $batch_month = Carbon::parse($collection_batch->collectionDate)->format('m');
                $diff = $current_month - $batch_month;

                if ($diff > 1 || $diff < 1) {
                    //do nothing
                } else {
                    //installment_ids
                    $installment_ids = explode(",", $collection_batch->installment_ids);

                    //find installments where status=FAILED or FUNDSCHECK
                    $installments = Installment::whereIn('id', $installment_ids)->where(function ($q) {
                            $q->orWhere('status', 'FAILED')
                            ->orWhere('status', 'FUNDSCHECK');
                        })->where('remarks', '!=', 'Account Is Valid')->get();

                    if ($installments->isNotEmpty()) {
                        //1. Create new collection batch
                        $batch = CollectionBatch::create([
                            "partner_id" => $collection_batch->partner->id,
                            "collectionReference" => $deductionService->getCollectionReference(),
                            "corporateReference" => $collection_batch->corporateReference,
                            "selectedPostingDate" => Carbon::now(),
                            "createdBy" => 1,
                            "verifiedBy" => 1,
                            "collectionDate" => $collection_batch->collectionDate,
                            "createdDate" => Carbon::now(),
                            "verifyDate" => Carbon::now(),
                        ]);

                        if ($batch->wasRecentlyCreated) {
                            //2.1 Declare variables
                            $number_of_collections = 0;
                            $arr_installments = [];
                            $arr_accounts = [];

                            //3. Create CSV file based installment
                            $csv = "SERIAL No.;BANK CODE;BRANCH NAME;EMPLOYEE NUMBER;CUSTOMER NAME;ACCOUNT NO;AMOUNT\n";

                            foreach ($installments as $installment) {
                                //account details
                                $account_name = strtoupper($installment->debitOrder->customerAccount->account_name);
                                $account_number = $installment->debitOrder->customerAccount->account_number;
                                $mandate_reference = $installment->debitOrder->reference;
                                $debit_order_id = $installment->debitOrder->id;
                                $periodicity = $installment->debitOrder->periodicity;
                                $installment_id = $installment->id;
                                $payment_amount = $installment->installment_amount;
                                $payment_date = $installment->payment_date;
                                $debit_start_date = Carbon::parse($installment->debitOrder->start_date)->toDateString();
                                $debit_end_date = Carbon::parse($installment->debitOrder->end_date)->toDateString();
                                $deleted = $installment->debitOrder->deleted;

                                //1.0: check if periodicity == MONTHLY and deleted = 0
                                if ($periodicity == "MONTHLY" && $deleted == 0) {
                                    //2.0: Check if mandate start date <= today
                                    if ($debit_start_date <=Carbon::now()->toDateString()) {
                                        //2.1: check if debit mandate expire
                                        if ($debit_end_date >= Carbon::now()->toDateString()) {
                                            //3.0: check if account exists in this batch
                                            if (!in_array($account_number, $arr_accounts)) {
                                                //4.0: check if the mandate reference debited this month
                                                $collection_debit_order = CollectionDebitOrder::where(['debit_order_id' => $debit_order_id, 'status' => 'CREATED'])
                                                    ->whereMonth('payment_at', Carbon::parse($payment_date)->format('m'));

                                                if ($collection_debit_order->count() == 1) {
                                                    //create csv file
                                                    $csv .= "{$installment_id};016;NMB;{$mandate_reference};{$account_name};{$account_number};{$payment_amount}\n";

                                                    //append account in array
                                                    array_push($arr_accounts, $account_number);

                                                    //update array installments
                                                    array_push($arr_installments, $installment_id);

                                                    //change payment date to this month
                                                    //original date
                                                    $original = Carbon::parse($payment_date);

                                                    // Get current month and year
                                                    $currentMonth = now()->month;
                                                    $currentYear = now()->year;

                                                    // Adjust year if original was in December and we're now in January
                                                    $newYear = ($original->month === 12 && $currentMonth === 1) ? $original->year + 1 : $original->year;

                                                    // Try to build new date with current month, original day and adjusted year
                                                    try {
                                                        $newDate = Carbon::create($newYear, $currentMonth, $original->day);
                                                    } catch (\Exception $e) {
                                                        // Day is invalid for the current month (e.g., Feb 30)
                                                        $newDate = Carbon::create($newYear, $currentMonth, 1)->endOfMonth();
                                                    }

                                                    // update collection debit order
                                                    $collection_debit_order = $collection_debit_order->first();
                                                    $collection_debit_order->payment_at = $newDate;
                                                    $collection_debit_order->save();

                                                    //increment number of collections
                                                    $number_of_collections += 1;
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            //check if installments is not null
                            if ($arr_installments) {
                                $batch->request_csv = $csv;
                                $batch->number_of_collections = $number_of_collections;
                                $batch->installment_ids = implode(",", $arr_installments);
                                $batch->save();

                                //Batch Information
                                $collection_reference = $batch->collectionReference;

                                //send Notification of batch creation
                                $message = "Collection batch number " . $collection_reference . " created. Review and approve to the next stage.";
                                $arr_phone = [255717705746, 255656314330];

                                foreach ($arr_phone as $phone) {
                                    //send sms
                                    $data = ["phone" => $phone, "message" => $message];
                                    dispatch(new SendSMSJob($data));
                                }
                            } else {
                                $batch->delete();
                            }
                        }
                    }
                }
            }
        }

        // Log the collection batches
        Log::debug("== Success on creating failed collection ==");
        return 200;
    }
}
