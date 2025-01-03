<?php

namespace App\Http\Controllers;

use App\Classes\Messaging;
use App\Models\CollectionBatch;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
use App\Services\DeductionService;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function __construct(protected BankPayloadService $payloadService, protected BankApiService $bankApi)
    {
        $this->middleware('auth');
    }

    public function batches(Request $request)
    {
        $title = 'Mandate Collection';

        $batches = CollectionBatch::filterCondition();

        if (isset($_POST['filter'])) {
            //all request
            $requests = $request->all();

            $batches = $batches->when($requests, function ($query) use ($requests) {
                $query->withFilters($requests);
            });

            $batches = $batches->get();
        } else {
            $batches = $batches->paginate(50);
        }

        $post_data = [
            'start_at' => $request->start_at ?? null,
            'end_at' => $request->end_at ?? null,
            'partner_id' => $request->partner_id ?? null,
        ];

        //render view
        return view('collections.batches', compact('title', 'batches', 'post_data'));
    }

    public function create(Request $request) {}

    public function edit(Request $request) {}


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
