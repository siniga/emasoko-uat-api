<?php

namespace App\Http\Controllers;

use App\Classes\Messaging;
use App\Http\Requests\CollectionRequest;
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

        //users
        $user = User::findOrFail(Auth::user()->id);

        $provider = null;
        if ($user->partners->where('authorize', '=', 1)->count() == 1)
            $provider = $user->partners[0];

        //providers
        $providers = Partner::filterCondition();
        $providers = $providers->get();

        //render view
        return view('collections.create', compact('title', 'providers', 'provider'));
    }

    public function store(CollectionRequest $request)
    {
        // validated request
        $validated = $request->validated();

        //post data
        $partner_id = $validated['provider_id'];
        $debit_date = $validated['created_at'];

        //debit orders => status=OPEN and NEXT_DEBIT_DATE = today()
        $partner = Partner::whereHas('debitOrders', function ($query) use ($debit_date) {
            return $query->where(['contract_status' => 'OPEN', 'channel' => 'NMB', 'approved' => 1, 'deleted' => 0])
                ->whereDate('next_debit_date', "=", $debit_date);
        })->where('partners.id', '=', $partner_id)->first();

        if ($partner) {
            //1. Select debit orders based of partner
            $debit_orders = DebitOrder::where(['contract_status' => 'OPEN', 'approved' => 1])
                ->where('partner_id', '=', $partner->id)
                ->where('deleted', '=', 0)
                ->whereDate('next_debit_date', "<=", $debit_date);

            if ($debit_orders->count() > 0) {
                //declare data points
                $data_point = 0;

                //2. Create collection batch based on partner 
                $batch = CollectionBatch::create([
                    'partner_id' => $partner->id,
                    "collectionReference" => $this->deductionService->getCollectionReference(),
                    "corporateReference" => $partner->reference,
                    "selectedPostingDate" => $debit_date,
                    "createdBy" => Auth::user()->id,
                    "verifiedBy" => Auth::user()->id,
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
                        $installments = Installment::where(['debit_order_id' => $val->id, 'status' => 'OPEN', 'pushed' => 0])
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

                                //update installment
                                $installment = Installment::where(['id' => $installment->id])->first();
                                $installment->pushed = 1;
                                $installment->save();
                            }
                        }

                        //update debit date
                        $this->deductionService->updateNextDebitDate($val->id, $val->next_debit_date);
                    }
                }

                //check if data point > 0
                if ($data_point > 0) {
                    //4. Save csv payload in database
                    $batch->request_csv = $csv;
                    $batch->save();

                    //5. Create payload for collection 
                    // $payload = $this->payloadService->collectionPayload($batch, $csv);

                    // //6. Send payload to a server
                    // $response = $this->bankApi->collectionCreate($payload);
                }
            }

            //redirect
            return Redirect::route('collections.create')->with('success', 'Collection batch created and pushed');
        } else {
            //redirect
            return Redirect::route('collections.create')->with('danger', 'No any Collection batch in this date');
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

        //send request
        $response = $this->bankApi->collectionStatus($payload);

        //response
        return response()->json(['success' => true, 'success_msg' => "Collection status successful fetched."], 200);
    }
}
