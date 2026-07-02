<?php

namespace App\Http\Controllers\Api\CRDB;

use App\Classes\Checksum;
use App\Http\Controllers\Controller;
use App\Jobs\CRDBCollectionJob;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\PaymentSettlement;
use App\Services\DeductionService;
use App\Services\ExternalCallbackService;
use App\Services\SettlementPostingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MandateController extends Controller
{
    private $auth;

    public function __construct(
        protected DeductionService $deductionService,
        protected SettlementPostingService $settlementPostingService,
        protected ExternalCallbackService $externalCallbackService
    )
    {
        $this->auth = new Checksum();
    }

    //push mandate to CRDB
    public function create(Request $request)
    {
        //predefined variables
        $debitAccount = $request->debitAccount;
        $phone = $request->phone;
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $totalAmount = $request->totalAmount;
        $installmentAmount = $request->installmentAmount;
        $periodicity = $request->periodicity;
        $frequency = $request->frequency;

        //validate mandatory fields
        $partnerId = config('services.crdb.partner_id');
        $requestId = Str::uuid()->toString();

        //data and salt
        $data = $requestId . substr($debitAccount, -4) . $partnerId;

        //create checksum
        $checksum = $this->auth->generateChecksum($data);
        Log::debug("== checksum ==");
        Log::debug($checksum);

        //token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();
        Log::debug("== AUTH Token ==");
        Log::debug($AUTH_BEARER_TOKEN);

        //headers
        $headers = [
            'Channel' => config('services.crdb.channel_id'),
            'AppId' => config('services.crdb.app_id'),
            'AppVersion' => '1.0.0',
            "RequestId" => $requestId,
            'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        Log::debug("== Headers ==");
        Log::debug($headers);

        //create payload
        $payload = [
            "PartnerId" =>  $partnerId,
            "RequestId" => $requestId,
            'Checksum' => $checksum,
            "PhoneNumber" =>  $phone,
            "DebitAccount" =>  $debitAccount,
            "TotalAmount" =>  $totalAmount,
            "InstallmentAmount" => $installmentAmount,
            "StartDate" => $startDate,
            "EndDate" => $endDate,
            "FrequencyType" => $periodicity,
            "Frequency" => $frequency,
            "BillDetails" => "Recurring payment"
        ];

        Log::debug("== CRDB Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = config('services.crdb.api_url');
        Log::debug("== CRDB API URL ==");
        Log::debug($API_URL);

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== CRDB MandateRegistration Response ==');
        Log::debug($response);

        return $response;
    }

    //cancellation
    public function cancel(Request $request)
    {
        //get data
        $requestId = $request->requestId;
        $debitAccount = $request->debitAccount;
        $phone = $request->phone;
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $totalAmount = $request->totalAmount;
        $installmentAmount = $request->installmentAmount;
        $periodicity = $request->periodicity;
        $frequency = $request->frequency;

        //validate mandatory fields
        $partnerId = config('services.crdb.partner_id');

        //data and salt
        $data = $requestId . substr($debitAccount, -4) . $partnerId;

        //create checksum
        $checksum = $this->auth->generateChecksum($data);

        Log::debug("== checksum ==");
        Log::debug($checksum);

        $AUTH_BEARER_TOKEN = $this->auth->generateToken();
        Log::debug("== AUTH Token ==");
        Log::debug($AUTH_BEARER_TOKEN);

        //headers
        $headers = [
            'Channel' => config('services.crdb.channel_id'),
            'AppId' => config('services.crdb.app_id'),
            'AppVersion' => '1.0.0',
            "RequestId" => $requestId,
            'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        Log::debug("== Headers ==");
        Log::debug($headers);

        //create payload
        $payload = [
            "PartnerId" =>  $partnerId,
            "RequestId" => $requestId,
            'Checksum' => $checksum,
            "PhoneNumber" =>  $phone,
            "DebitAccount" =>  $debitAccount,
            "TotalAmount" =>  $totalAmount,
            "InstallmentAmount" => $installmentAmount,
            "StartDate" => $startDate,
            "EndDate" => $endDate,
            "FrequencyType" => $periodicity,
            "Frequency" => $frequency,
            "BillDetails" => "Recurring payment"
        ];

        Log::debug("== CRDB Cancellation Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = config('services.crdb.api_url');
        Log::debug("== CRDB API URL ==");
        Log::debug($API_URL);

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== CRDB Cancellation Response ==');
        Log::debug($response);

        return $response;
    }

    //callback function from CRDB
    public function callback(Request $request)
    {
        //logging response
        Log::debug('== CRDB Callback Response ==');
        Log::debug($request->all());

        // check for valid callback type
        $callback_type = $request->input('callbackType');

        if ($callback_type == "REQUEST_STATUS") {
            Log::debug('== CRDB Mandate Request Status Callback ==');
            Log::debug($request->all());

            $data = $request->input('data', []);
            $mandateReference = $data['reference'] ?? null;
            $approved = $data['status'] ?? null;

            if (!$mandateReference) {
                Log::warning('Invalid CRDB request status callback payload.', [
                    'payload' => $request->all(),
                ]);

                return response()->json([
                    'status' => false,
                    'message' => 'Invalid callback payload',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            //query debit order by reference id
            $debitOrder = DebitOrder::where('crdb_reference', '=', $mandateReference);

            if ($debitOrder->exists()) {
                $debitOrder = $debitOrder->first();
                $previousLifecycleStatus = $debitOrder->resolved_lifecycle_status;
                $remarks = $data['message']
                    ?? $data['statusDescription']
                    ?? $data['statusDesc']
                    ?? null;

                if (strtolower($approved) == "approved") {
                    $debitOrder->approved = 1;
                    $debitOrder->status = 240;
                    $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_ACTIVE;
                    $debitOrder->remarks = $remarks ?: "Mandate approved";
                    $debitOrder->save();

                    if ($previousLifecycleStatus !== DebitOrder::LIFECYCLE_ACTIVE) {
                        $this->deductionService->createInstallment($debitOrder);
                    }

                    //Log response
                    Log::debug("== CRDB Mandate Status Approved ==");
                } else {
                    // Failed debit orders
                    $debitOrder->approved = 2;
                    $debitOrder->status = 500;
                    $debitOrder->lifecycle_status = DebitOrder::LIFECYCLE_FAILED;
                    $debitOrder->remarks = $remarks ?: "Failed to approve mandate.";
                    $debitOrder->save();

                    //Log response
                    Log::debug("== Failed to approve CRDB Mandate ==");
                }

                $debitOrder->refresh();

                if ($previousLifecycleStatus !== $debitOrder->resolved_lifecycle_status) {
                    $this->externalCallbackService->dispatchMandateStatus(
                        $debitOrder,
                        strtolower($approved) == "approved" ? 'MANDATE_ACTIVE' : 'MANDATE_FAILED'
                    );
                }
            }
        } else if ($callback_type == "PAYMENT_STATUS") {
            // mandate reference
            $mandateReference = $request['data']['reference'];

            //query debit order by reference id
            $debitOrder = DebitOrder::where('crdb_reference', '=', $mandateReference);

            if ($debitOrder->exists()) {
                $debitOrder = $debitOrder->first();

                //dispatch to background job for processing
                dispatch(new CRDBCollectionJob($debitOrder, $request->all()));
            }
        }

        // response
        return response()->json([
            'status' => true,
            'message' => "Callback received",
        ], 200);
    }


}
