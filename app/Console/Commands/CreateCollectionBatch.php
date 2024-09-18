<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Models\Installment;
use App\Models\Partner;
use App\Services\BankPayloadService;
use App\Services\BankApiService;
use App\Services\DeductionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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

        //debit orders => status=OPEN and NEXT_DEBIT_DATE = today()
        $partners = Partner::whereHas('debitOrders', function ($query) {
            return $query->where('contract_status', '=', 'OPEN')
                ->whereBetween('next_debit_date', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()]);
        })->get();

        foreach ($partners as $partner) {
            //1. Select debit orders based of partner
            $debit_orders = $partner->debitOrders->where('contract_status', '=', 'OPEN')
                ->where('deleted', '=', 0)
                ->whereBetween('next_debit_date', [Carbon::now()->startOfDay(), Carbon::now()->endOfDay()]);

            if ($debit_orders->count() > 0) {
                //2. Create collection batch based on partner 
                $batch = CollectionBatch::create([
                    "collectionReference" => $deductionService->getCollectionReference(),
                    "corporateReference" => $partner->reference,
                    "selectedPostingDate" => null,
                    "createdBy" => 1,
                    "verifiedBy" => 1,
                    "createdDate" => Carbon::now(),
                    "verifyDate" => Carbon::now(),
                ]);

                if ($batch->wasRecentlyCreated) {
                    //3. Create CSV file based installment
                    $csv = "SERIAL No.;BANK CODE;BRANCH NAME;EMPLOYEE NUMBER;CUSTOMER NAME;ACCOUNT NO;AMOUNT\n";

                    foreach ($debit_orders as $val) {
                        $installments = Installment::where(['debit_order_id' => $val->id, 'status' => 'OPEN'])
                            ->whereDate('payment_date', '<=', $val->next_debit_date)->get();

                        foreach ($installments as $installment) {
                            $csv .= "{$installment->id};16;NMB;{$val->customerAccount->account_number};{$val->customerAccount->account_name};{$val->customerAccount->account_number};{$installment->installment_amount}\n";
                        }
                    }
                }

                //4. Create payload for collection 
                $payload = $bankPayload->collectionPayload($batch, $csv);

                //5. Send payload to a server
                $response = $bankApi->collectionCreate($payload);
            }
        }

        return 200;
    }
}
