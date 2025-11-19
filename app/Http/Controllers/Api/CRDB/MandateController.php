<?php

namespace App\Http\Controllers\Api\CRDB;

use App\Classes\Checksum;
use App\Http\Controllers\Controller;
use App\Models\DebitOrder;
use App\Services\DeductionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MandateController extends Controller
{
    private $auth;

    public function __construct(protected DeductionService $deductionService)
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
        $partnerId = env('CRDB_PARTNER_ID');
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
            'Channel' => env('CRDB_CHANNEL_ID'),
            'AppId' => env('CRDB_APP_ID'),
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
        $API_URL = env('CRDB_API_URL');
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
        $partnerId = env('CRDB_PARTNER_ID');

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
            'Channel' => env('CRDB_CHANNEL_ID'),
            'AppId' => env('CRDB_APP_ID'),
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
        $API_URL = env('CRDB_API_URL');
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
            //query debit order by reference id
            $debit_order = DebitOrder::where('crdb_reference', '=', $request['data']['reference'])->first();

            //check status
            if (strtolower($request['data']['status']) == "approved") {
                $debit_order->approved = 1;
                $debit_order->status = 240;
                $debit_order->remarks = "Mandate approved";
                $debit_order->save();

                //Log response
                Log::debug("== CRDB Mandate Status Approved ==");

                // create installments
                $this->deductionService->createInstallment($debit_order);
            } else {
                $debit_order->status = 500;
                $debit_order->remarks = "Failed to approve mandate.";
                $debit_order->save();

                //Log response
                Log::debug("== Failed to approve CRDB Mandate ==");
            }
        } else if ($callback_type == "PAYMENT_STATUS") {
            //update installment amount
        }
    }
}
