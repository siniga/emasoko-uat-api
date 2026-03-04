<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Models\Installment;
use Illuminate\Console\Command;

class FormatFailedBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'format:failed-batches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to format failed batch collections';

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
        //Query filed collection batches
        $collection_batches = CollectionBatch::whereIn('collectionReference', [17916, 17832, 17748])->get();

        if ($collection_batches) {
            foreach ($collection_batches as $batch) {
                // check for duplicate from collection using installment ids
                $installment_ids = explode(",", $batch->installment_ids);
                $installments = Installment::whereIn('id', $installment_ids)->get();

                $arr_installments = [];
                $number_of_collections = 0;
                //3. Create CSV file based installment
                $csv = "SERIAL No.;BANK CODE;BRANCH NAME;EMPLOYEE NUMBER;CUSTOMER NAME;ACCOUNT NO;AMOUNT\n";
                foreach ($installments as $installment) {
                    $account_name = strtoupper($installment->debitOrder->customerAccount->account_name);
                    $account_number = $installment->debitOrder->customerAccount->account_number;
                    $mandate_reference = $installment->debitOrder->reference;
                    $debit_type = $installment->debitOrder->debit_type;
                    $installment_id = $installment->id;
                    $payment_amount = $installment->installment_amount;

                    if ($installment->status == 'FAILED' && ($installment->remarks == 'Item Can Be Collected And Has Been Tracked Successfully.' || $installment->remarks == 'Insufficient funds Archive')) {
                        if (($installment->installment_amount != $installment->debitOrder->installment_amount) || ($installment->installment_amount != $installment->debitOrder->max_installment_amount)) {
                            $installment->installment_amount = ($debit_type == 'FIXED') ? $installment->debitOrder->installment_amount : $installment->debitOrder->max_installment_amount;
                            $installment->min_installment_amount = $installment->debitOrder->min_installment_amount;
                            $installment->max_installment_amount = $installment->debitOrder->max_installment_amount;
                            $installment->current_balance = 0;
                            $installment->outstanding_amount = ($debit_type == 'FIXED') ? $installment->debitOrder->installment_amount : $installment->debitOrder->max_installment_amount;;
                            $installment->save();

                            //update payment amount
                            $payment_amount = $installment->debitOrder->installment_amount;
                        }
                        //append installment to array
                        array_push($arr_installments, $installment->id);

                        //create csv
                        $csv .= "{$installment_id};016;NMB;{$mandate_reference};{$account_name};{$account_number};{$payment_amount}\n";

                        //increment number of collections
                        $number_of_collections += 1;
                    }
                }

                //update collection batch
                $batch->request_csv = $csv;
                $batch->number_of_collections = $number_of_collections;
                $batch->installment_ids = implode(",", $arr_installments);
                $batch->save();
            }
        }

        return 200;
    }
}
