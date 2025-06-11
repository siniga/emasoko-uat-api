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
        //Limit
        $limit = 250;
        $current_date = Carbon::now(); //today batch
        //$current_date = Carbon::now()->addDays(8); //8 days from today 25-05
        Log::debug("== Creating collection batch: '{$current_date}' ==");

        //initiate collection date
        $collection_date = Carbon::now();

        //initialize deduction service
        $deductionService = new DeductionService();

        $partners = Partner::whereHas('debitOrders', function ($query) use ($current_date) {
            return $query->where(['contract_status' => 'OPEN', 'channel' => 'NMB', 'approved' => 1, 'status' => 240, 'deleted' => 0])
                ->whereDate('next_debit_date', "=", $current_date);
        })->where('partners.status', '=', 202)->get();

        foreach ($partners as $partner) {
            //1.0: Number of installment
            $number_installments = Installment::getCreated($partner, $current_date)->count();

            if ($number_installments > 0) {
                //looping
                $looping = $number_installments / $limit;

                //iterate looping
                for ($i = 1; $i <= ceil($looping); $i++) {
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
                        $number_of_collections = 0;
                        $arr_installments = [];
                        $arr_accounts = [];

                        //3. Create CSV file based installment
                        $csv = "SERIAL No.;BANK CODE;BRANCH NAME;EMPLOYEE NUMBER;CUSTOMER NAME;ACCOUNT NO;AMOUNT\n";

                        //installments
                        $installments = Installment::getCreated($partner, $current_date)->take($limit)->get();

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
                            $debit_start_date = Carbon::parse($installment->debitOrder->start_date);
                            $debit_end_date = Carbon::parse($installment->debitOrder->end_date);
                            $deleted = $installment->debitOrder->deleted;

                            //update collection date
                            $collection_date = $installment->payment_date;

                            //1.0: check if periodicity == MONTHLY and deleted = 0
                            if ($periodicity == "MONTHLY" && $deleted == 0) {
                                //2.0: Check if mandate start date <= today
                                if ($debit_start_date->lte(Carbon::now())) {
                                    //2.1: check if debit mandate expire
                                    if ($debit_end_date->gte(Carbon::now())) {
                                        //3.0: check if account exists in this batch
                                        if (!in_array($account_number, $arr_accounts)) {
                                            //4.0: check if the mandate reference debited this month
                                            $collection_debit_order = CollectionDebitOrder::where(['debit_order_id' => $debit_order_id])
                                                ->whereMonth('payment_at', Carbon::parse($payment_date)->format('m'));

                                            if ($collection_debit_order->count() == 0) {
                                                //create csv file
                                                $csv .= "{$installment_id};016;NMB;{$mandate_reference};{$account_name};{$account_number};{$payment_amount}\n";

                                                //append account in array
                                                array_push($arr_accounts, $account_number);

                                                //update array installments
                                                array_push($arr_installments, $installment_id);

                                                //update installment status => created = 1
                                                $up_installment = Installment::findOrFail($installment_id);
                                                $up_installment->created = 1;
                                                $up_installment->save();

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

                        //check if installments is not null
                        if ($arr_installments) {
                            $batch->request_csv = $csv;
                            $batch->collectionDate = $collection_date;
                            $batch->number_of_collections = $number_of_collections;
                            $batch->installment_ids = implode(",", $arr_installments);
                            $batch->save();

                            //Batch Information
                            $collection_reference = $batch->collectionReference;

                            //send Notification of batch creation
                            $message = "Collection batch number " . $collection_reference . " created. Review and approve to the next stage.";
                            $arr_phone = [255717705746, 255656314330];
                            //$arr_phone = [255717705746, 255656314330, 255629602290, 255684512512];

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
        Log::debug("== Success on creating collection batch ==");
        return 200;
    }
}
