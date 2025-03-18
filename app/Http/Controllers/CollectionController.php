<?php

namespace App\Http\Controllers;

use App\Classes\Messaging;
use App\Exports\CollectionCreatedExport;
use App\Exports\CollectionProcessedExport;
use App\Http\Requests\CollectionRequest;
use App\Jobs\CollectionStatusJob;
use App\Jobs\PushCollectionJob;
use App\Jobs\SendSMSJob;
use App\Models\CollectionBatch;
use App\Models\CollectionDebitOrder;
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
use Maatwebsite\Excel\Facades\Excel;

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

        //providers
        $providers = Partner::filterCondition();
        $providers = $providers->get();

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
        return view('collections.batches', compact('title', 'batches', 'providers', 'number_of_batches', 'post_data'));
    }

    public function create(Request $request)
    {
        $title = 'Create Mandate Collection';

        $limit = 250; //batch limit
        $partner_id = null;
        $arr_accounts = [];
        $arr_installments = [];

        if (isset($_POST['filter'])) {
            //all request
            $requests = $request->all();
            $partner_id =  $requests['partner_id'];

            $debit_orders = DebitOrder::where(['contract_status' => 'OPEN', 'channel' => 'NMB', 'approved' => 1, 'status' => 240, 'deleted' => 0])
                ->where('partner_id', '=', $requests['partner_id'])
                ->whereDate('next_debit_date', "=", $requests['debit_date'])
                ->whereDate('end_date', '>=', Carbon::now())
                ->take($limit);

            if ($debit_orders->count() > 0) {
                //debit orders
                $debit_orders = $debit_orders->orderBy('created_at', 'asc')->get();

                foreach ($debit_orders as $val) {
                    $installment = Installment::where(['debit_order_id' => $val->id, 'status' => 'OPEN', 'created' => 0, 'pushed' => 0])
                        ->whereDate('payment_date', '=', $val->next_debit_date);

                    if ($installment->count() > 0) {
                        $installment = $installment->first();

                        //account details
                        $account_name = strtoupper($installment->debitOrder->customerAccount->account_name);
                        $account_number = $installment->debitOrder->customerAccount->account_number;
                        $mandate_reference = $installment->debitOrder->reference;
                        $debit_order_id = $installment->debitOrder->id;
                        $periodicity = $installment->debitOrder->periodicity;
                        $installment_id = $installment->id;
                        $payment_amount = $installment->installment_amount;
                        $payment_date = $installment->payment_date;
                        $debit_end_date = Carbon::parse($installment->debitOrder->end_date);
                        $deleted = $installment->debitOrder->deleted;

                        //1.0 check if periodicity == MONTHLY
                        if ($periodicity == "MONTHLY") {
                            //1.1: check if debit mandate expire
                            if ($debit_end_date->gte(Carbon::now()) && $deleted == 0) {
                                //2.0: check if account exists in this batch
                                if (!in_array($account_number, $arr_accounts)) {
                                    //3.0: check if the mandate reference debited this month
                                    $collection_debit_order = CollectionDebitOrder::where(['debit_order_id' => $debit_order_id])
                                        ->where(function ($query) {
                                            $query->where('status', 'CREATED')
                                                ->orWhere('status', 'SUCCESS');
                                        })->whereMonth('payment_at', Carbon::parse($payment_date)->format('m'));

                                    if ($collection_debit_order->count() == 0) {
                                        //append account in array
                                        array_push($arr_accounts, $account_number);

                                        //append array installments
                                        $arr_installments[] = [
                                            'id' => $installment->id,
                                            'reference' => $val->reference,
                                            'account_name' => strtoupper($val->customerAccount->account_name),
                                            'account_number' => $val->customerAccount->account_number,
                                            'amount' => $installment->installment_amount,
                                            'current_balance' => $installment->current_balance,
                                            'outstanding_amount' => $installment->outstanding_amount,
                                            'payment_date' => $installment->payment_date,
                                        ];
                                    }
                                }
                            }
                        }
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
                $number_of_collections = 0;
                $arr_installments = [];
                $arr_accounts = [];

                //3. Create CSV file based installment
                $csv = "SERIAL No.;BANK CODE;BRANCH NAME;EMPLOYEE NUMBER;CUSTOMER NAME;ACCOUNT NO;AMOUNT\n";

                foreach ($request->installment_ids as $id) {
                    //Query Installment
                    $installment = Installment::findOrFail($id);

                    //Account Information
                    $account_name = strtoupper($installment->debitOrder->customerAccount->account_name);
                    $account_number = $installment->debitOrder->customerAccount->account_number;
                    $mandate_reference = $installment->debitOrder->reference;
                    $debit_order_id = $installment->debitOrder->id;
                    $periodicity = $installment->debitOrder->periodicity;
                    $installment_id = $installment->id;
                    $payment_amount = $installment->installment_amount;
                    $payment_date = $installment->payment_date;
                    $debit_end_date = Carbon::parse($installment->debitOrder->end_date);
                    $deleted = $installment->debitOrder->deleted;

                    //1.0 check if periodicity == MONTHLY
                    if ($periodicity == "MONTHLY") {
                        //1.0: check if debit mandate expire
                        if ($debit_end_date->gte(Carbon::now()) && $deleted == 0) {
                            //2.0: check if account exists in this batch
                            if (!in_array($account_number, $arr_accounts)) {
                                //3.0: check if the mandate reference debited this month
                                $collection_debit_order = CollectionDebitOrder::where(['debit_order_id' => $debit_order_id])
                                    ->where(function ($query) {
                                        $query->where('status', 'CREATED')
                                            ->orWhere('status', 'SUCCESS');
                                    })->whereMonth('payment_at', Carbon::parse($payment_date)->format('m'));

                                if ($collection_debit_order->count() == 0) {
                                    //create csv file
                                    $csv .= "{$installment_id};016;NMB;{$mandate_reference};{$account_name};{$account_number};{$payment_amount}\n";

                                    //append account in array
                                    array_push($arr_accounts, $account_number);

                                    //update array installments
                                    array_push($arr_installments, $installment->id);

                                    //update installment
                                    $installment->created = 1;
                                    $installment->save();

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

                //4. Save csv payload in database
                $batch->request_csv = $csv;
                $batch->number_of_collections = $number_of_collections;
                $batch->installment_ids = implode(",", $arr_installments);
                $batch->save();
            }

            //Batch Information
            $collection_reference = $batch->collectionReference;

            //send Notification of batch creation
            $message = "Collection batch number " . $collection_reference . " created. Review and approve to the next stage.";
            $arr_phone = [255717705746, 255656314330, 255629602290, 255684512512];

            foreach ($arr_phone as $phone) {
                //send sms
                $data = ["phone" => $phone, "message" => $message];
                dispatch(new SendSMSJob($data));
            }

            //redirect
            return Redirect::route('collections.create')->with('success', 'Collection batch created, Preview and Approve to next stage.');
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
        $arr_installments = [];
        $installment_ids = explode(',', $batch->installment_ids);
        $installments = Installment::whereIn('id', $installment_ids);

        if ($installments->count() > 0) {
            //installments
            $installments = $installments->get();

            foreach ($installments as $val) {
                $arr_installments[] = [
                    'id' => $val->id,
                    'reference' => $val->debitOrder->reference,
                    'periodicity' => $val->debitOrder->periodicity,
                    'account_name' => strtoupper($val->debitOrder->customerAccount->account_name),
                    'account_number' => $val->debitOrder->customerAccount->account_number,
                    'amount' => $val->installment_amount,
                    'last_paid_amount' => $val->last_paid_amount,
                    'current_balance' => $val->current_balance,
                    'outstanding_amount' => $val->outstanding_amount,
                    'status' => $val->status,
                    'remarks' => $val->remarks,
                    'payment_date' => Carbon::parse($val->payment_date)->format('d-m-Y'),
                    'end_date' => Carbon::parse($val->debitOrder->end_date)->format('d-m-Y'),
                ];
            }
        }

        //render view
        return view('collections.show', compact('title', 'batch', 'arr_installments'));
    }

    //export xls
    public function export_created($id)
    {
        $arr_installments = [];

        //batch
        $batch = CollectionBatch::where(['id' => $id])->firstOrFail();

        //installments
        $installment_ids = explode(',', $batch->installment_ids);
        $installments = Installment::whereIn('id', $installment_ids);

        if ($installments->count() > 0) {
            //installments
            $installments = $installments->get();

            foreach ($installments as $val) {
                $arr_installments[] = [
                    'reference' => $val->debitOrder->reference,
                    'periodicity' => $val->debitOrder->periodicity,
                    'account_name' => strtoupper($val->debitOrder->customerAccount->account_name),
                    'account_number' => $val->debitOrder->customerAccount->account_number,
                    'amount' => $val->installment_amount ?? 0,
                    'payment_date' => Carbon::parse($val->payment_date)->format('d-m-Y'),
                    'end_date' => Carbon::parse($val->debitOrder->end_date)->format('d-m-Y'),
                ];
            }
        }

        //create filename
        $filename = $batch->collectionReference . "_" . $batch->partner->business_name . ".xlsx";

        //return
        return Excel::download(new CollectionCreatedExport($arr_installments), $filename);
    }

    //export xls
    public function export_processed($id)
    {
        $arr_installments = [];

        //batch
        $batch = CollectionBatch::where(['id' => $id])->firstOrFail();

        //installments
        $installment_ids = explode(',', $batch->installment_ids);
        $installments = Installment::whereIn('id', $installment_ids);

        if ($installments->count() > 0) {
            //installments
            $installments = $installments->get();

            foreach ($installments as $val) {
                // format status
                if ($val->status == "CLOSED")
                    $status = "PAID";
                elseif ($val->status == "FAILED")
                    $status = "UNPAID";
                else
                    $status = strtoupper($val->status);


                $arr_installments[] = [
                    'reference' => $val->debitOrder->reference,
                    'account_name' => strtoupper($val->debitOrder->customerAccount->account_name),
                    'account_number' => $val->debitOrder->customerAccount->account_number,
                    'amount' => $val->installment_amount ?? 0,
                    'last_paid_amount' => $val->last_paid_amount ?? 0,
                    'outstanding_amount' => $val->outstanding_amount,
                    'status' => $status,
                    'remarks' => $val->remarks,
                    'payment_date' => Carbon::parse($val->payment_date)->format('d-m-Y')
                ];
            }
        }

        //create filename
        $filename = $batch->collectionReference . "_" . $batch->partner->business_name . ".xlsx";

        //return
        return Excel::download(new CollectionProcessedExport($arr_installments), $filename);
    }

    public function upload(Request $request)
    {
        $title = "Upload Collection Batch";

        //collection batch
        $collection_batch = CollectionBatch::where(['id' => $id])->firstOrFail();

        if (!empty($collection_batch->request_csv)) {
            //1. Create parameters
            $csv = $collection_batch->request_csv;
            $arr_installments = explode(",", $collection_batch->installment_ids);

            //2. Create payload for collection 
            $payload = $this->payloadService->collectionPayload($collection_batch, $csv);

            //3. BackgroundJob for creating collection batches
            dispatch(new PushCollectionJob($payload, $arr_installments));
        }

        //response
        return response()->json(['success' => true, 'success_msg' => "Collection batch uploaded."], 200);
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

    public function create_debit_orders()
    {
        $batches = CollectionBatch::where(['deleted' => 0])->get();

        foreach ($batches as $batch) {
            //installments
            $installment_ids = explode(',', $batch->installment_ids);
            $installments = Installment::whereIn('id', $installment_ids);

            if ($installments->count() > 0) {
                //installments
                $installments = $installments->get();

                foreach ($installments as $installment) {
                    if ($installment->status == 'CLOSED' || $installment->status == 'SUCCESS')
                        $status = 'SUCCESS';
                    else
                        $status = 'CREATED';

                    //create or update collection batch
                    $collection_debit_order = CollectionDebitOrder::firstOrNew(
                        ['debit_order_id' => $installment->debitOrder->id, 'payment_at' => $installment->payment_date]
                    );
                    $collection_debit_order->collection_batch_id = $batch->id;
                    $collection_debit_order->status = $status;
                    $collection_debit_order->save();
                }
            }
        }

        return response()->json(['error' => false, 'message' => 'Collection debit orders created']);
    }

    public function update_debit_orders()
    {
        $batches = CollectionBatch::where(['deleted' => 0])->get();

        foreach($batches as $val){
            $collection_debit_order = CollectionDebitOrder::where(['collection_batch_id' => $val->id]);

            if($collection_debit_order->count() > 0){
                $number_of_collection = $collection_debit_order->count();

                //update 
                $collection_debit_order = $collection_debit_order->first();
                $collection_debit_order->number_of_collection = $number_of_collection;
                $collection_debit_order->save();
            }
        }

        return response()->json(['error' => false, 'success' => 'Debit order updated']);
    }
}
