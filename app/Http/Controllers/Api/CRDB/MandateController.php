<?php

namespace App\Http\Controllers\Api\CRDB;

use App\Classes\Authentication;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MandateController extends Controller
{
    private $auth;

    function __construct()
    {
        $this->auth = new Authentication();
    }

    public function create(Request $request)
    {
        $AUTH_BEARER_TOKEN = "";

        //headers
        $headers = [
            'channel' => 'thirdparty',
            'Accept' => 'application/json',
            'checksum' => 'd7267c3cab7ba4111c7c4f9e6e725c4a4a33c803e341cc0f431fcde2cd41b4c9',
            'Content-Type' => 'application/json'
        ];

        //create payload
        $payload = [
            "partnerId" =>  "762-983-029",
            "requestId" => "8e207710e-ea3e-4f02-9568-be2d9ade7432",
            "phoneNumber" =>  "255717705746",
            "debitAccount" =>  "0112478119100",
            "totalAmount" =>  "100.00",
            "startDate" => "31-08-2024",
            "endDate" => "31-10-2024",
            "installmentAmount" => "10.00",
            "frequencyType" => "DAILY",
            "frequency" => "1",
            "billDetails" => "Recurring payment"
        ];

        Log::debug("== Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = "";

        //push payload => NMB Direct Debits
        $response = Http::withOptions(['verify' => false])->withHeaders($headers)->post($API_URL, $payload);

        Log::debug("Response");
        Log::debug($response->status());
        Log::debug($response);

        //send response to eMakato
        return response()->json($response->json());
    }
}
