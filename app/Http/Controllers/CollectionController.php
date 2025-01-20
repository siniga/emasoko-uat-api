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

        $partner_id = null;
        $arr_installments = [];

        if (isset($_POST['filter'])) {
            //all request
            $requests = $request->all();
            $partner_id =  $requests['partner_id'];

            $debit_orders = DebitOrder::where(['contract_status' => 'OPEN', 'approved' => 1, 'status' => 240])
                ->where('partner_id', '=', $requests['partner_id'])
                ->where('deleted', '=', 0)
                ->whereDate('next_debit_date', "=", $requests['debit_date']);

            if ($debit_orders->count() > 0) {
                foreach ($debit_orders->get() as $val) {
                    $installments = Installment::where(['debit_order_id' => $val->id, 'status' => 'OPEN', 'pushed' => 0])
                        ->whereDate('payment_date', '=', $val->next_debit_date)->get();

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

        //render view
        return view('collections.create', compact('title', 'providers', 'provider', 'partner_id', 'arr_installments'));
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

        //Convert the data into an array of rows
        $rows = explode("\n", $batch->response_csv);

        //remove empty rows
        $rows = array_filter($rows);

        //create arrray
        $arr_data = [];

        //2. process installment
        foreach ($rows as $index => $row) {
            // Skip the first row as it contains headers
            if ($index === 0) {
                continue;
            }

            // Split the row into individual fields using semicolon as delimiter
            $fields = explode(";", $row);
            $payment_status = $fields[7];
            $paid_amount = floatval($fields[6]);

            //get debit mandate 
            $debit_order = DebitOrder::where(['reference' => $fields[3]]);

            if ($debit_order->count() > 0) {
                $debit_order = $debit_order->first();

                //get installment from id
                $installment = Installment::where('id', '=', $fields[0])->first();

                if ($installment) {
                    //create array data
                    $arr_data[] = [
                        'id' => $fields[0],
                        'reference' => $debit_order->reference,
                        'account_name' => $fields[4],
                        'account_number' => $fields[5],
                        'debit_type' => $debit_order->debit_type,
                        'expected_amount' => ($debit_order->debit_type == 'FIXED') ? $debit_order->installment_amount : $debit_order->max_installment_amount,
                        'paid_amount' => ($payment_status == 'SUCCESS') ? $paid_amount : 0,
                        'current_balance' => $installment->current_balance,
                        'outstanding_amount' => $installment->outstanding_amount,
                        'payment_date' => $installment->payment_date,
                        'payment_status' => $payment_status,
                        'message' => $fields[8]
                    ];
                }
            }
        }

        //render view
        return view('collections.show', compact('title', 'batch', 'arr_data'));
    }

    public function push(Request $request)
    {
        $title = "Push Collection Batch";

        //collection batch
        $collection_batch = CollectionBatch::where(['id' => $request->batch_id])->firstOrFail();
    }

    public function fetch_status(Request $request)
    {
        $title = "Fetch Collection Status";

        //collection batch
        $collection_batch = CollectionBatch::where(['id' => $request->batch_id])->firstOrFail();

        //create payload
        $payload = $this->payloadService->collectionStatusPayload($collection_batch);

        //3. BackgroundJob for creating collection batches
        dispatch(new CollectionStatusJob($payload));

        //response
        return response()->json(['success' => true, 'success_msg' => "Collection status are processed, Wait for 2-5 minutes and refresh the page."], 200);
    }
}
