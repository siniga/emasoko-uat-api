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
    public function __construct(protected DeductionService $deductionService)
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
        $previous_month = now()->subMonth()->month;
        $current_year = now()->year;
        Log::debug("== Previous Month: '{$previous_month}' ==");
        Log::debug("== Current Year: '{$current_year}' ==");

        //initiate collection date
        $batch_collection_date = Carbon::now();

        //collection batches
        $collection_batches = CollectionBatch::whereMonth("created_at", "=", $previous_month)
            ->whereYear("created_at", "=", $current_year)
            ->where("partner_id", "!=", 22)
            ->where('collectionReference', 24699)
            ->orderBy('collectionReference', 'ASC');

        if ($collection_batches->count() > 0) {
            //collection batches
            $collection_batches = $collection_batches->get();

            foreach ($collection_batches as $collection_batch) {
                //installment_ids
                $installment_ids = explode(",", $collection_batch->installment_ids);

                //find installments where status=FAILED
                $installments = Installment::whereIn('id', $installment_ids)->where(function ($q) {
                    $q->orWhere('status', 'FAILED')
                        ->orWhere('status', 'FUNDSCHECK');
                })->where('remarks', '!=', 'Account Is Valid')->get();

                if ($installments->isNotEmpty()) {
                    //1. Create new collection batch
                    $batch = CollectionBatch::create([
                        "partner_id" => $collection_batch->partner->id,
                        "collectionReference" => $this->deductionService->getCollectionReference(),
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
                            $effective_date = $installment->effective_payment_date;
                            $debit_start_date = Carbon::parse($installment->debitOrder->start_date);
                            $debit_end_date = Carbon::parse($installment->debitOrder->end_date);
                            $deleted = $installment->debitOrder->deleted;
                            $contract_status = $installment->debitOrder->contract_status;

                            //Batch Collection Date
                            $batch_collection_date = $installment->effective_payment_date;
                            Log::debug("== Failed collection date ==");
                            Log::debug($batch_collection_date);

                            //1.0: check if periodicity == MONTHLY and deleted = 0
                            if ($periodicity == "MONTHLY" && $contract_status == "OPEN" && $deleted == 0) {
                                //2.0: Check if mandate start date <= today
                                if ($debit_start_date->lte(Carbon::now())) {
                                    //2.1: check if debit mandate expire
                                    if ($debit_end_date->gte(Carbon::now())) {
                                        //3.0: check if account exists in this batch
                                        if (!in_array($account_number, $arr_accounts)) {
                                            // 3.1: create new collection date from previous month
                                            $collection_date = $this->deductionService->createCollectionDate($payment_date);

                                            //4.0: check if the mandate reference debited this month
                                            $collection_debit_order = CollectionDebitOrder::where(['debit_order_id' => $debit_order_id])
                                                ->whereMonth('payment_at', Carbon::parse($collection_date)->format('m'))
                                                ->whereYear('payment_at', Carbon::parse($collection_date)->format('Y'));

                                            if (!$collection_debit_order->exists()) {
                                                //create csv file
                                                $csv .= "{$installment_id};016;NMB;{$mandate_reference};{$account_name};{$account_number};{$payment_amount}\n";

                                                //append account in array
                                                array_push($arr_accounts, $account_number);

                                                //update array installments
                                                array_push($arr_installments, $installment_id);

                                                //update installment status => created = 1 and effective payment date
                                                $updated_installment = Installment::findOrFail($installment_id);
                                                $updated_installment->effective_payment_date = $collection_date;
                                                $updated_installment->created = 1;
                                                $updated_installment->save();

                                                // create collection debit order
                                                CollectionDebitOrder::create([
                                                    'collection_batch_id' => $batch->id,
                                                    'debit_order_id' => $debit_order_id,
                                                    'installment_id' => $installment_id,
                                                    'status' => 'CREATED',
                                                    'payment_at' => $collection_date
                                                ]);

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
                            $batch->collectionDate = $batch_collection_date;
                            $batch->number_of_collections = $number_of_collections;
                            $batch->installment_ids = implode(",", $arr_installments);
                            $batch->save();
                        } else {
                            $batch->delete();
                        }
                    }
                }
            }
        }

        //TODO: Messaging module

        //send Notification of batch creation
        $message = "Collection batches created. Review and approve batches to the next stage.";
        $arr_phone = [255717705746, 255656314330];

        foreach ($arr_phone as $phone) {
            //send sms
            $data = ["phone" => $phone, "message" => $message];
            dispatch(new SendSMSJob($data));
        }

        // Log the collection batches
        Log::debug("== {$previous_month} - {$current_year} failed collection batches created ==");
        return 200;
    }
}
