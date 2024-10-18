<?php

namespace App\Http\Controllers\Api\CRDB;

use App\Classes\Checksum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Str;

class MandateController extends Controller
{
    private $auth;

    function __construct()
    {
        $this->auth = new Checksum();
    }

    //push mandate to CRDB
    public function create(Request $request)
    {
        //predefined variables
        $debitAccount = "0112478119100";
        $phone = "255717705746";
        $partnerId = "7c0e38cf-1f74-4fc1-83d0-abd4afb5b9d9";
        $requestId = Str::uuid()->toString();

        //data and salt
        $data = $requestId . substr($debitAccount, -4) . $partnerId;
        $salt = "dPQ11iVNGI2aLnCUGdQ3LQA2tCx00WzTxj6zPsdLK3vvR34n5J9s4U4kwzMguTTlahYY66yr6ieqbo7CfGZsVOlt8xKZr+Jon7jc599v9Zkw94ZIpI6Y3YmgXwLZql9nGsgWyA==";
        //create checksum
        $checksum = $this->auth->generateChecksum($data, $salt);
        Log::debug("== checksum ==");
        Log::debug($checksum);

        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //headers
        $headers = [
            'Channel' => 'IB',
            'AppId' => 'INTERNET',
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
            "TotalAmount" =>  "100.00",
            "InstallmentAmount" => "10.00",
            "StartDate" => "15-10-2024",
            "EndDate" => "31-12-2024",
            "FrequencyType" => "DAILY",
            "Frequency" => "1",
            "BillDetails" => "Recurring payment"
        ];

        Log::debug("== Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = "https://pre-prod-esb-wso2am-gw.crdbbank.co.tz/common/direct-debit/1.0.0/mandate";

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug("== Response ==");
        Log::debug($response->status());
        Log::debug($response);

        //send response to eMakato
        return response()->json($response->json());
    }

    //callback function from CRDB
    public function callback(Request $request)
    {
        // Log the callback response for debugging
        Log::info('Payment Callback', $request->all());

        return response()->json(['message' => 'Payment successful'], 200);
    }
}
