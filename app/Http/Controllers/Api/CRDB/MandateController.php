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

    public function create(Request $request)
    {
        //predefined variables
        $debitAccount = "0112478119100";
        $phone = "255717705746";
        $partnerId = "fcc19daa-68c9-482e-86cd-a5f1d64cb8d2";
        $requestId = Str::uuid()->toString();

        //data and salt
        $data = $requestId . substr($debitAccount, -4) . $partnerId;
        $salt = "kdaz9/m4nSLNUGoFLc5SPSKG/n63wMBTo9Ws3kvbXELm/D0gbAhJpeyQfvhp+Kv7i78TFg6BvOV+64LYax2JSCJcO5ySF1bLSraJUvYbgmUIMKIzP4TBIJQxzPULIHAylLbLMA==";

        //create checksum
        $checksum = $this->auth->generateChecksum($data, $salt);
        Log::debug("== checksum ==");
        Log::debug($checksum);

        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //headers
        $headers = [
            'channel' => 'thirdparty',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN,
            'Content-Type' => 'application/json'
        ];

        Log::debug("== Headers ==");
        Log::debug($headers);

        //create payload
        $payload = [
            "partnerId" =>  $partnerId,
            'checksum' => $checksum,
            "requestId" => $requestId,
            "phoneNumber" =>  $phone,
            "debitAccount" =>  $debitAccount,
            "totalAmount" =>  "100.00",
            "startDate" => "25-09-2024",
            "endDate" => "31-12-2024",
            "installmentAmount" => "10.00",
            "frequencyType" => "DAILY",
            "frequency" => "1",
            "billDetails" => "Recurring payment"
        ];

        Log::debug("== Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = "https://pre-prod-esb-wso2am-gw.crdbbank.co.tz";

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug("== Response ==");
        Log::debug($response->status());
        Log::debug($response);

        //send response to eMakato
        return response()->json($response->json());
    }
}
