<?php

namespace App\Http\Controllers\Api\Airtel;

use App\Classes\AirtelToken;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MandateController extends Controller
{
    private $auth;

    public function __construct()
    {
        $this->auth = new AirtelToken();
    }

    //push mandate to Airtel
    public function create(Request $request)
    {
        $AUTH_BEARER_TOKEN = $this->auth->generate();

        //headers
        $headers = [
            'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN,
            'Content-Type' => 'application/json',
            'Accept' => '*/*'
        ];

        //create payload
        $payload = [
            "reference" => "Testing transaction",
            "subscriber" => [
                "country" => "TZ",
                "currency" => "TZS",
                "msisdn" => 785670229
            ],
            "transaction" => [
                "amount" => 1000,
                "country" => "TZ",
                "currency" => "TZS",
                "id" => "ANN011891009781"
            ]
        ];

        Log::debug("== AIRTEL Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = 'https://openapiuat.airtel.africa/merchant/v1/payments/';

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== AIRTEL MandateRegistration Response ==');
        Log::debug($response);

        return $response;
    }

    //callback function from Airtel
    public function callback(Request $request)
    {
        //logging response
        Log::debug('== Airtel Callback Response ==');
        Log::debug($request->all());
    }
}
