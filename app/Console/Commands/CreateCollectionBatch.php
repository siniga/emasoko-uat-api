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
    protected $signature = 'create:batches';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create collection bacthes';

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

        //debit orders => status=OPEN and NEXT_DEBIT_DATE = today()
        $partners = Partner::whereHas('debitOrders', function ($query) {
            return $query->where(['contract_status' => 'OPEN', 'channel' => 'NMB', 'approved' => 1, 'deleted' => 0])
                ->whereDate('next_debit_date', "=", Carbon::now());
        })->get();

        foreach ($partners as $partner) {
            //1. Select debit orders based of partner
            $debit_orders = DebitOrder::where(['contract_status' => 'OPEN', 'approved' => 1])
                ->where('partner_id', '=', $partner->id)
                ->where('deleted', '=', 0)
                ->whereDate('next_debit_date', "=", Carbon::now());

            if ($debit_orders->count() > 0) {
                //declare data points
                $data_point = 0;

                //2. Create collection batch based on partner 
                $batch = CollectionBatch::create([
                    'partner_id' => $partner->id,
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

                        $arr_accounts = [];
                        foreach ($installments as $installment) {
                            //account details
                            $account_name = strtoupper($val->customerAccount->account_name);
                            $account_number = $val->customerAccount->account_number;
                            $mandate_reference = $val->reference;
                            $installment_id = $installment->id;
                            $payment_amount = $installment->installment_amount;

                            //check if account exists in this batch
                            if (!in_array($account_number, $arr_accounts)) {
                                //create csv file
                                $csv .= "{$installment_id};016;NMB;{$mandate_reference};{$account_name};{$account_number};{$payment_amount}\n";

                                //increment data point
                                $data_point += 1;

                                //append account in array
                                array_push($arr_accounts, $account_number);
                            }
                        }

                        //update debit date
                        $deductionService->updateNextDebitDate($val->id, $val->next_debit_dat);
                    }
                }

                //check if data point > 0
                if ($data_point > 0) {
                    //4. Save csv payload in database
                    $batch->request_csv = $csv;
                    $batch->save();
                }
            }
        }

        return 200;
    }
}
