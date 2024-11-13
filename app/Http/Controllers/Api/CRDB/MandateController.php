<?php

namespace App\Http\Controllers\Api\CRDB;

use App\Classes\Checksum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        $debitAccount = "0152503822900";
        $phone = "255762412130";
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
            "StartDate" => "25-10-2024",
            "EndDate" => "31-12-2024",
            "FrequencyType" => "DAILY",
            "Frequency" => "1",
            "BillDetails" => "Recurring payment"
        ];

        Log::debug("== Payload ==");
        Log::debug($payload);
    }

    //callback function from CRDB
    public function callback(Request $request)
    {
        // Log the callback response for debugging
        // Log::info('Payment Callback', $request->all());
        // Log::info('Status Code => ', $request['ResponseHeader']);

        //return $request['ResponseHeader']['StatusCode'];

        // return response()->json(['message' => 'Payment successful'], 200);
    }
}
