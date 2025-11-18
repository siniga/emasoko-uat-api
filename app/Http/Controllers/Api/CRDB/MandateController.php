<?php

namespace App\Http\Controllers\Api\CRDB;

use App\Classes\Checksum;
use App\Http\Controllers\Controller;
use App\Models\DebitOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MandateController extends Controller
{
    private $auth;

    public function __construct()
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
        $salt = env('CRDB_SALT');

        //create checksum
        $checksum = $this->auth->generateChecksum($data);

        Log::debug("== checksum ==");
        Log::debug($checksum);

        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

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

        //validate mandatory fields
        $partnerId = env('CRDB_PARTNER_ID');

        //data and salt
        $data = $requestId . substr($debitAccount, -4) . $partnerId;

        //create checksum
        $checksum = $this->auth->generateChecksum($data);

        Log::debug("== checksum ==");
        Log::debug($checksum);

        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

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
        $payload = [];

        Log::debug("== CRDB Cancellation Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = env('CRDB_API_URL') . '/' . $requestId . '/cancel';

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== CRDB Cancellation Response ==');
        Log::debug($response);

        return $response;
    }

    public function request()
    {
        // response
        return response()->json([
            "ResponseHeader" => [
                "StatusCode"     => 1000,
                "StatusMessage"  => "Success",
                "StatusDesc"     => "OK",
                "RequestId"      => "99f1d1fc-b25a-4444-bf0b-8fb1982ec240",
            ],
            "ResponseBody" => [
                "RequestId" => "99f1d1fc-b25a-4444-bf0b-8fb1982ec240",
                "Reference" => 10188,
            ]
        ]);
    }

    public function response()
    {
        $httpResponse = $this->request();

        // Convert response into array
        Log::debug('== MandateRegistration Response ==');
        Log::debug($httpResponse);

        // Convert to array (not JsonResponse object)
        $response = $httpResponse->getData(true);

        // // Safely read values
        $statusCode = data_get($response, 'ResponseHeader.StatusCode');
        $statusDesc = data_get($response, 'ResponseHeader.StatusDesc');
        $ref        = data_get($response, 'ResponseBody.Reference');

        //Handle business logic only if StatusCode is present
        if ($statusCode === 1000) {
            Log::debug($statusCode);
            Log::debug($statusDesc);
            Log::debug($ref);
            // $debit_order->status = 201;  // success
            // if ($ref) {
            //     $debit_order->crdb_reference = $ref;
            // }
        } elseif ($statusCode === 1001) {
            //$debit_order->status = 403;  // failure
        } else {
            // Unknown/other code: record it for investigation
            Log::warning('MandateRegistration returned unexpected StatusCode', [
                'StatusCode' => $statusCode,
                'response'    => $response,
            ]);
        }

        return $response;
    }

    //callback function from CRDB
    public function callback(Request $request)
    {
        // //logging response
        // Log::debug('== Callback Response ==');
        // Log::debug($request->all());

        // // check for valid callback type
        // $callback_type = $request->input('callbackType');

        // if ($callback_type == "REQUEST_STATUS") {
        //     //query debit order by reference id
        //     $debit_order = DebitOrder::where('crdb_reference', '=', $request['data']['reference'])->first();

        //     //check status
        //     if (strtolower($request['data']['status']) == "approved") {
        //         $debit_order->approved = 1;
        //         $debit_order->status = 240; //approved
        //     } else if (strtolower($request['data']['status']) == "cancelled") {
        //         $debit_order->status = 501;
        //     } else {
        //         $debit_order->status = 501;
        //     }
        //     $debit_order->save();
        // } else if ($callback_type == "PAYMENT_STATUS") {
        //     //update installment amount
        // }
    }
}
