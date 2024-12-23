<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Models\Installment;
use App\Models\Partner;
use App\Models\DebitOrder;
use App\Services\BankPayloadService;
use App\Services\BankApiService;
use App\Services\DeductionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateCollectionBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:batches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create and Send payment bacthes';

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

        $created_date = "2024-12-19";
        //$created_date = Carbon::now();
 
        //debit orders => status=OPEN and NEXT_DEBIT_DATE = today()
        $partners = Partner::whereHas('debitOrders', function ($query) {
            return $query->where(['contract_status' => 'OPEN', 'channel' => 'NMB'  ,'approved' => 1, 'deleted' => 0, 'partner_id' => 14])
                ->whereDate('next_debit_date', "=", "2024-12-20");
        })->get();

        foreach ($partners as $partner) {
            //1. Select debit orders based of partner
            $debit_orders = DebitOrder::where(['contract_status' => 'OPEN', 'approved' => 1])
                ->where('partner_id', '=', $partner->id)
                ->where('deleted', '=', 0)
                ->whereDate('next_debit_date', "=", "2024-12-20");

                Log::debug("== debit orders ==");
                Log::debug($debit_orders->count());


            if ($debit_orders->count() > 0) {
                //declare data points
                $data_point = 0;

                //2. Create collection batch based on partner 
                $batch = CollectionBatch::create([
                    "collectionReference" => $deductionService->getCollectionReference(),
                    "corporateReference" => $partner->reference,
                    "selectedPostingDate" => Carbon::now(),
                    "createdBy" => 1,
                    "verifiedBy" => 1,
                    "createdDate" => Carbon::now(),
                    "verifyDate" => Carbon::now(),
                ]);

                if ($batch->wasRecentlyCreated) {
                    //debit orders
                    $debit_orders = $debit_orders->get();

                    //3. Create CSV file based installment
                    $csv = "SERIAL No.;BANK CODE;BRANCH NAME;EMPLOYEE NUMBER;CUSTOMER NAME;ACCOUNT NO;AMOUNT\n";

                    foreach ($debit_orders as $val) {
                        // Your Eloquent query executed by using get()
                        $installments = Installment::where(['debit_order_id' => $val->id, 'status' => 'OPEN'])
                            ->whereDate('payment_date', '<=', $val->next_debit_date)->get();

                        foreach ($installments as $installment) {
                            //account details
                            $bank_code = $val->customerAccount->bank_code;
                            $account_name = strtoupper($val->customerAccount->account_name);
                            $account_number = $val->customerAccount->account_number;
                            $mandate_reference = $val->reference;

                            //create csv file
                            $csv .= "{$installment->id};016;NMB;{$mandate_reference};{$account_name};{$account_number};{$installment->installment_amount}\n";

                            //increment data point
                            $data_point += 1;
                        }
                    }
                }

                //check if data point > 0
                if ($data_point > 0) {
                    //4. Save csv payload in database
                    $batch->request_csv = $csv;
                    $batch->save();

                    //5. Create payload for collection 
                    $payload = $bankPayload->collectionPayload($batch, $csv);

                    //6. Send payload to a server
                    $response = $bankApi->collectionCreate($payload);
                }
            }
        }

        return 200;
    }
}
