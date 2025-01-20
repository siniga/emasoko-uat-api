<?php

namespace App\Console\Commands;

use App\Jobs\PushCollectionJob;
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
        $bankPayload = new BankPayloadService();

        //debit orders => status=OPEN and NEXT_DEBIT_DATE = today()
        // ->where("partners.id", "!=", 10)
        $partners = Partner::whereHas('debitOrders', function ($query) {
            return $query->where(['contract_status' => 'OPEN', 'channel' => 'NMB', 'approved' => 1, 'deleted' => 0])
                ->whereDate('next_debit_date', "=", Carbon::now());
        })->get();

        foreach ($partners as $partner) {
            //1.0 Select debit orders based of partner
            $debit_orders = DebitOrder::where(['contract_status' => 'OPEN', 'approved' => 1])
                ->where('partner_id', '=', $partner->id)
                ->where('deleted', '=', 0)
                ->whereDate('next_debit_date', "=", Carbon::now());

            if ($debit_orders->count() > 0) {
                //2.0 Create collection batch based on partner 
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
                    //2.1 Declare variables
                    $arr_installments = [];

                    //debit orders
                    $debit_orders = $debit_orders->get();

                    //3. Create CSV file based installment
                    $csv = "SERIAL No.;BANK CODE;BRANCH NAME;EMPLOYEE NUMBER;CUSTOMER NAME;ACCOUNT NO;AMOUNT\n";

                    foreach ($debit_orders as $val) {
                        // Your Eloquent query executed by using get()
                        $installments = Installment::where(['debit_order_id' => $val->id, 'status' => 'OPEN', 'pushed' => 0])
                            ->whereDate('payment_date', '=', $val->next_debit_date)->get();

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

                                //append account in array
                                array_push($arr_accounts, $account_number);

                                //update array installments
                                array_push($arr_installments, $installment->id);
                            }
                        }
                    }

                    //check if installments is not null
                    if ($arr_installments) {
                        //4. Save csv payload in database
                        $batch->request_csv = $csv;
                        $batch->installment_ids = implode(",", $arr_installments);
                        $batch->save();
                    } else {
                        //Delete the batch
                        $batch->delete();
                    }
                }
            }
        }

        return 200;
    }
}
