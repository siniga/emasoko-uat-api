<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Models\CollectionDebitOrder;
use App\Models\Installment;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
use App\Services\DeductionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateFailedCollectionBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:failed-batches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for sending failed batch ';

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
        //initialize deduction service
        $deductionService = new DeductionService();

        //FAILED collection batch
        $collection_batches = CollectionBatch::where('collectionReference', '=', 10167)->get();

        if ($collection_batches) {
            foreach ($collection_batches as $batch) {
                //1. Create new collection batch
                $new_batch = CollectionBatch::create([
                    "partner_id" => $batch->partner_id,
                    "collectionReference" => $deductionService->getCollectionReference(),
                    "corporateReference" => $batch->corporateReference,
                    "selectedPostingDate" => Carbon::now(),
                    "createdBy" => 1,
                    "verifiedBy" => 1,
                    "createdDate" => Carbon::now(),
                    "verifyDate" => Carbon::now(),
                ]);

                if ($new_batch->wasRecentlyCreated) {
                    //3. Create CSV file based installment
                    $csv = "SERIAL No.;BANK CODE;BRANCH NAME;EMPLOYEE NUMBER;CUSTOMER NAME;ACCOUNT NO;AMOUNT\n";

                    //response
                    $response_csv = $batch->request_csv;

                    // Convert the data into an array of rows
                    $rows = explode("\n", $response_csv);

                    //remove empty rows
                    $rows = array_filter($rows);

                    //Declare variables
                    $number_of_collections = 0;
                    $arr_installments = [];
                    $arr_accounts = [];

                    foreach ($rows as $index => $row) {
                        // Skip the first row as it contains headers
                        if ($index === 0) {
                            continue;
                        }

                        // Split the row into individual fields using semicolon as delimiter
                        $fields = explode(";", $row);

                        //get installment from id
                        $installment = Installment::where(['id' => $fields[0]])->first();

                        if ($installment) {
                            //account details
                            $account_name = strtoupper($installment->debitOrder->customerAccount->account_name);
                            $account_number = $installment->debitOrder->customerAccount->account_number;
                            $mandate_reference = $installment->debitOrder->reference;
                            $debit_order_id = $installment->debitOrder->id;
                            $periodicity = $installment->debitOrder->periodicity;
                            $installment_id = $installment->id;
                            $payment_amount = $installment->installment_amount;
                            $payment_date = $installment->payment_date;
                            $debit_start_date = Carbon::parse($installment->debitOrder->start_date);
                            $debit_end_date = Carbon::parse($installment->debitOrder->end_date);
                            $deleted = $installment->debitOrder->deleted;

                            //updated installment set created = 0 and pushed = 0
                            $installment->created = 0;
                            $installment->pushed = 0;
                            $installment->status = "OPEN";
                            $installment->save();

                            //1.0: Delete the collection_debit_order where payment_date and debit_order_id
                            $delete_cdo = CollectionDebitOrder::where(['debit_order_id' => $debit_order_id])
                                ->whereMonth('payment_at', Carbon::parse($payment_date)->format('m'))->delete();

                            //2.0: check if periodicity == MONTHLY and deleted = 0
                            if ($periodicity == "MONTHLY" && $deleted == 0) {
                                //3.0: Check if mandate start date <= today
                                if ($debit_start_date->lte(Carbon::now())) {
                                    //3.1: check if debit mandate expire
                                    if ($debit_end_date->gte(Carbon::now())) {
                                        //4.0: check if account exists in this batch
                                        if (!in_array($account_number, $arr_accounts)) {
                                            //5.0: check if the mandate reference debited this month
                                            $collection_debit_order = CollectionDebitOrder::where(['debit_order_id' => $debit_order_id])
                                                ->whereMonth('payment_at', Carbon::parse($payment_date)->format('m'));

                                            if ($collection_debit_order->count() == 0) {
                                                //create csv file
                                                $csv .= "{$installment_id};016;NMB;{$mandate_reference};{$account_name};{$account_number};{$payment_amount}\n";

                                                //append account in array
                                                array_push($arr_accounts, $account_number);

                                                //update array installments
                                                array_push($arr_installments, $installment_id);

                                                //updated installment set created = 1
                                                $installment->created = 1;
                                                $installment->save();

                                                // create collection debit order
                                                $create_collection_debit_order = CollectionDebitOrder::create([
                                                    'collection_batch_id' => $batch->id,
                                                    'debit_order_id' => $debit_order_id,
                                                    'status' => 'CREATED',
                                                    'payment_at' => $payment_date
                                                ]);

                                                //increment number of collections
                                                $number_of_collections += 1;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    //check if installments is not null
                    if ($arr_installments) {
                        $new_batch->request_csv = $csv;
                        $new_batch->number_of_collections = $number_of_collections;
                        $new_batch->installment_ids = implode(",", $arr_installments);
                        $new_batch->save();
                    } else {
                        $new_batch->delete();
                    }
                }

                //set old batch deleted = 1
                $old_batch = CollectionBatch::where(['id' => $batch->id])->firstOrFail();
                $old_batch->deleted = 1;
                $old_batch->save();
            }
        }

        return 200;
    }
}
