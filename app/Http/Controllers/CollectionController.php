<?php

namespace App\Http\Controllers;

use App\Classes\Messaging;
use App\Http\Requests\CollectionRequest;
use App\Jobs\CollectionStatusJob;
use App\Jobs\PushCollectionJob;
use App\Models\CollectionBatch;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\Partner;
use App\Models\User;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
use App\Services\DeductionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class CollectionController extends Controller
{
    public function __construct(protected BankPayloadService $payloadService, protected BankApiService $bankApi, protected DeductionService $deductionService)
    {
        $this->middleware('auth');
    }

    public function batches(Request $request)
    {
        $title = 'Mandate Collection';

        //collection batches
        $number_of_batches = 0;
        $batches = CollectionBatch::filterCondition();

        if (isset($_POST['filter'])) {
            //all request
            $requests = $request->all();

            $batches = $batches->when($requests, function ($query) use ($requests) {
                $query->withFilters($requests);
            });

            $number_of_batches = $batches->count();
            $batches = $batches->get();
        } else {
            $number_of_batches = $batches->count();
            $batches = $batches->paginate(50);
        }

        $post_data = [
            'start_at' => $request->start_at ?? null,
            'end_at' => $request->end_at ?? null,
            'partner_id' => $request->partner_id ?? null,
        ];

        //render view
        return view('collections.batches', compact('title', 'batches', 'number_of_batches', 'post_data'));
    }

    public function create(Request $request)
    {
        $title = 'Create Mandate Collection';

        $limit = 250; //batch limit
        $partner_id = null;
        $arr_installments = [];

        if (isset($_POST['filter'])) {
            //all request
            $requests = $request->all();
            $partner_id =  $requests['partner_id'];

            $debit_orders = DebitOrder::where(['contract_status' => 'OPEN', 'channel' => 'NMB', 'approved' => 1, 'status' => 240, 'deleted' => 0])
                ->where('partner_id', '=', $requests['partner_id'])
                ->whereDate('next_debit_date', "=", $requests['debit_date'])
                ->whereDate('end_date', '>=', Carbon::now());

            if ($debit_orders->count() > 0) {
                foreach ($debit_orders->get() as $val) {
                    $installments = Installment::where(['debit_order_id' => $val->id, 'status' => 'OPEN', 'created' => 0, 'pushed' => 0])
                        ->whereDate('payment_date', '=', $val->next_debit_date)->take($limit)->get();

                    foreach ($installments as $v) {
                        $arr_installments[] = [
                            'id' => $v->id,
                            'reference' => $val->reference,
                            'account_name' => strtoupper($val->customerAccount->account_name),
                            'account_number' => $val->customerAccount->account_number,
                            'amount' => $v->installment_amount,
                            'current_balance' => $v->current_balance,
                            'outstanding_amount' => $v->outstanding_amount,
                            'payment_date' => $v->payment_date,
                        ];
                    }
                }
            } else {
                //redirect
                return Redirect::route('collections.create')->with('danger', 'No any collection on this ' . $requests['debit_date']);
            }
        }

        //users
        $user = User::findOrFail(Auth::user()->id);

        $provider = null;
        if ($user->partners->where('authorize', '=', 1)->count() == 1)
            $provider = $user->partners[0];

        //providers
        $providers = Partner::filterCondition();
        $providers = $providers->get();

        //maximum date
        $max_date = Carbon::now()->addDays(5)->format('Y-m-d');

        //render view
        return view('collections.create', compact('title', 'max_date', 'providers', 'provider', 'partner_id', 'arr_installments'));
    }

    public function store(Request $request)
    {
        if ($request->selected_partner_id && $request->installment_ids) {
            //1. Find partner information
            $partner = Partner::findOrFail($request->selected_partner_id);

            //2.0 Create collection batch based on partner 
            $batch = CollectionBatch::create([
                'partner_id' => $partner->id,
                "collectionReference" => $this->deductionService->getCollectionReference(),
                "corporateReference" => $partner->reference,
                "selectedPostingDate" => Carbon::now(),
                "createdBy" => Auth::user()->id,
                "verifiedBy" => Auth::user()->id,
                "createdDate" => Carbon::now(),
                "verifyDate" => Carbon::now(),
            ]);

            if ($batch->wasRecentlyCreated) {
                //2.1 Declare variables
                $arr_installments = [];

                //3. Create CSV file based installment
                $csv = "SERIAL No.;BANK CODE;BRANCH NAME;EMPLOYEE NUMBER;CUSTOMER NAME;ACCOUNT NO;AMOUNT\n";

                $arr_accounts = []; //declare array accounts
                foreach ($request->installment_ids as $id) {
                    //installment
                    $installment = Installment::findOrFail($id);

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
                        array_push($arr_installments, $installment->id);
                    }
                }

                //4. Save csv payload in database
                $batch->request_csv = $csv;
                $batch->installment_ids = implode(",", $arr_installments);
                $batch->save();

                //5. Create payload for collection 
                $payload = $this->payloadService->collectionPayload($batch, $csv);

                //BackgroundJob for creating collection batches
                dispatch(new PushCollectionJob($payload, $arr_installments));
            }

            //redirect
            return Redirect::route('collections.create')->with('success', 'Collection batch created.');
        } else {
            //redirect
            return Redirect::route('collections.create')->with('danger', 'No any collection selected.');
        }
    }

    public function show($id)
    {
        $title = "Collection Batch Information";

        //batch
        $batch = CollectionBatch::where(['id' => $id])->firstOrFail();

        //installments
        $installment_ids = explode(',', $batch->installment_ids);
        $installments = Installment::whereIn('id', $installment_ids)->get();

        //render view
        return view('collections.show', compact('title', 'batch', 'installments'));
    }

    public function push(Request $request)
    {
        $title = "Push Collection Batch";

        //collection batch
        $collection_batch = CollectionBatch::where(['id' => $request->batch_id])->firstOrFail();

        if (!empty($collection_batch->request_csv)) {
            //1. Create parameters
            $csv = $collection_batch->request_csv;
            $arr_installments = explode(",", $collection_batch->installment_ids);
            Log::debug($arr_installments);

            //2. Create payload for collection 
            $payload = $this->payloadService->collectionPayload($collection_batch, $csv);

            //3. BackgroundJob for creating collection batches
            dispatch(new PushCollectionJob($payload, $arr_installments));
        }

        //response
        return response()->json(['success' => true, 'success_msg' => "Collection batch pushed."], 200);
    }

    public function archive($id)
    {
        $title = 'Archive collection batch';

        //batch
        $batch = CollectionBatch::where(['id' => $id])->firstOrFail();
        $batch->deleted = 1;
        $batch->save();

        //redirect
        return Redirect::route('collections.batches')->with('success', 'Collection batch archived');
    }

    public function fetch_status(Request $request)
    {
        $title = "Fetch Collection Status";

        //Collection batch
        $collection_batch = CollectionBatch::where(['id' => $request->batch_id])->firstOrFail();

        //Create payload
        $payload = $this->payloadService->collectionStatusPayload($collection_batch);

        //BackgroundJob for creating collection batches
        dispatch(new CollectionStatusJob($payload));

        //response
        return response()->json(['success' => true, 'success_msg' => "Collection status successful fetched."], 200);
    }

    public function reconciliation(Request $request)
    {
        //read file
        $file = fopen(public_path('assets/NMB_file.csv'), 'r');
        fgetcsv($file); // Skip the first row

        //array of installments
        $arr_installments = [];

        //iterarate file
        while (($row = fgetcsv($file, 1000, ',')) !== false) {
            $mandate_ref = substr($row[4], -12);
            $paid_amount = $row[8];

            //mandate 
            $debit_order = DebitOrder::where(["reference" => $mandate_ref])->first();

            //query installment which are failed
            $installment = Installment::where(['debit_order_id' => $debit_order->id])
                ->whereMonth('payment_date', '02')->first();

            //installmentAmount
            $installmentAmount = $installment->installment_amount ?? $installment->max_installment_amount;

            //create array
            $arr_installments[] = [
                'installmentId' => $installment->id,
                'mandateReference' => $mandate_ref,
                'paymentDate' => $installment->payment_date,
                'paidAmount' => $paid_amount,
                'installmentAmount' => $installmentAmount,
                'NMB Status' => "PAID",
                "Status" => $installment->status
            ];

            //process calculation for installment and debit order.
            // if ($installment && $installment->status != "CLOSED"){
            //     $process_data = $this->deductionService->processInstallment($installment, $paid_amount, $mandate_ref);
            // }
        }
        fclose($file);

        echo "<pre>";
        print_r($arr_installments);

        // //create new file
        // $new_file = fopen(public_path("assets/NMB_New_File1.csv"), "w");

        // foreach ($arr_installments as $line) {
        //     fputcsv($new_file, $line);
        // }
          
        // fclose($new_file);

        //return response()->json(['success' => false, 'success_msg' => "File Processed"]);
    }
}
