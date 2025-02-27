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
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
            'X-Country' => 'TZ',
            'X-Currency' => 'TZS',
            'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN,
            // 'x-signature' => 'MGsp*****************Ag==',
            // 'x-key' => 'DVZC*******************NM='
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
                "amount" => 5000009,
                "country" => "TZ",
                "currency" => "TZS",
                "id" => "SOLOCODES113"
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

    //disbursment api
    public function disbursment(Request $request)
    {
        $AUTH_BEARER_TOKEN = $this->auth->generate();

        //headers
        $headers = [
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
            'X-Country' => 'TZ',
            'X-Currency' => 'TZS',
            'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN,
        ];

        //create payload
        $payload = array(
            'payee' => array(
                "currency" => "TZS",
                "msisdn" => "785670229",
                "name" => "JOSE"
            ),
            "reference" => "Testing transaction",
            'pin' => 'hus/I3gBrEAaGHhW34UBB/mOsQSnS4fzNZCDOwI9ciDHJ/RuOtwN+ZoJc31TrHVkl8pKjh4Gf4ENjcXHy8gFmU2g4NtV0gG9Q5oBhktupBPhcqjdVhvORMxZELtmu9fpZ1vivJ+HzUiaiBPUFQrwBkHUhG8KO4m/CXhCE2IVErg=',
            'transaction' => array(
                'amount' => 1000,
                'id' => 'SOLOCODE6930037',
                'type' => 'B2C',
            )
        );

        Log::debug("== AIRTEL Disbursment Payload ==");
        Log::debug($payload);

        //API URL
        //$API_URL = 'https://openapiuat.airtel.africa/standard/v1/disbursements/';
        $API_URL = 'https://openapiuat.airtel.africa/standard/v2/disbursements/';

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== AIRTEL Disbursment Response ==');
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
