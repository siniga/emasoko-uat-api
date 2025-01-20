<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Models\Installment;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
use App\Services\DeductionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

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

        //FAILED collection batch ->where('partner_id', '=', 10)
        $collection_batches = CollectionBatch::where('collectionReference', '=', 10065)->get();

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

                    //2820;016;NMB;231220242630;YUNICE MARWA THOBIAS;50810011811;225000

                    $response_csv = $batch->request_csv;

                    // Convert the data into an array of rows
                    $rows = explode("\n", $response_csv);

                    //remove empty rows
                    $rows = array_filter($rows);

                    //Declare variables
                    $arr_installments = [];
                    $arr_accounts = [];

                    foreach ($rows as $index => $row) {
                        // Skip the first row as it contains headers
                        if ($index === 0) {
                            continue;
                        }

                        // Split the row into individual fields using semicolon as delimiter
                        $fields = explode(";", $row);

                        //get response status
                        //$status = strtolower($fields[7]);

                        //take only FAILED status
                        //if ($status == "fail") {
                            //get installment from id
                        $installment = Installment::where(['id' => $fields[0]])->first();

                        if ($installment) {
                            //account details
                            $account_name = strtoupper($installment->debitOrder->customerAccount->account_name);
                            $account_number = $installment->debitOrder->customerAccount->account_number;
                            $mandate_reference = $installment->debitOrder->reference;
                            $installment_id = $installment->id;
                            $payment_amount = $installment->installment_amount;

                            //check if account exists in this batch
                            if (!in_array($account_number, $arr_accounts)) {
                                //create csv file
                                $csv .= "{$installment_id};016;NMB;{$mandate_reference};{$account_name};{$account_number};{$payment_amount}\n";

                                //append account in array
                                array_push($arr_accounts, $account_number);

                                //update array installments
                                array_push($arr_installments, $installment_id);
                            }
                        }
                        //}
                    }

                    //check if installments is not null
                    if ($arr_installments) {
                        // Save csv payload in database
                        $new_batch->request_csv = $csv;
                        $new_batch->installment_ids = implode(",", $arr_installments);
                        $new_batch->save();
                    }
                }

                //update batch FAILED = 0; 
                $batch->failed = 0;
                $batch->save();
            }
        }

        return 200;
    }
}
