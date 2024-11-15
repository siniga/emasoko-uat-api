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
    protected $signature = 'send:failed-batches';

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
        $bankPayload = new BankPayloadService();
        $bankApi =  new BankApiService();

        //FAILED collection batch
        $collection_batches = CollectionBatch::where('failed', '>', 0)->where(['batch_processed' => 1])->get();

        if ($collection_batches) {
            foreach ($collection_batches as $batch) {
                //1. Create new collection batch
                $new_batch = CollectionBatch::create([
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

                    $response_csv = $batch->response_csv;

                    // Convert the data into an array of rows
                    $rows = explode("\n", $response_csv);

                    //remove empty rows
                    $rows = array_filter($rows);

                    foreach ($rows as $index => $row) {
                        // Skip the first row as it contains headers
                        if ($index === 0) {
                            continue;
                        }

                        // Split the row into individual fields using semicolon as delimiter
                        $fields = explode(";", $row);

                        //get response status
                        $status = strtolower($fields[7]);

                        //take only FAILED status
                        if ($status == "fail") {
                            //get installment from id
                            $installment = Installment::where(['id' => $fields[0], 'status' => 'FAILED'])->first();

                            if ($installment) {
                                //account details
                                $bank_code = $installment->debitOrder->customerAccount->bank_code;
                                $account_name = strtoupper($installment->debitOrder->customerAccount->account_name);
                                $account_number = $installment->debitOrder->customerAccount->account_number;
                                $mandate_reference = $installment->debitOrder->reference;

                                //create csv file
                                $csv .= "{$installment->id};{$bank_code};NMB;{$mandate_reference};{$account_name};{$account_number};{$installment->installment_amount}\n";
                            }
                        }
                    }

                    //4. Save csv payload in database
                    $new_batch->request_csv = $csv;
                    $new_batch->save();

                    //5. Create payload for collection 
                    $payload = $bankPayload->collectionPayload($new_batch, $csv);

                    //6. Send payload to a server
                    $response = $bankApi->collectionCreate($payload);
                }

                //update batch FAILED = 0; 
                $batch->failed = 0;
                $batch->save();
            }
        }

        return 200;
    }
}
