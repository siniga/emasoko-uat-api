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
    // public function create(Request $request)
    // {
    //     $AUTH_BEARER_TOKEN = $this->auth->generate();

    //     //headers
    //     $headers = [
    //         'Accept' => '*/*',
    //         'Content-Type' => 'application/json',
    //         'X-Country' => 'TZ',
    //         'X-Currency' => 'TZS',
    //         'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN,
    //         // 'x-signature' => 'MGsp*****************Ag==',
    //         // 'x-key' => 'DVZC*******************NM='
    //     ];

    //     //create payload
    //     $payload = [
    //         "reference" => "Testing transaction",
    //         "subscriber" => [
    //             "country" => "TZ",
    //             "currency" => "TZS",
    //             "msisdn" => 785670229
    //         ],
    //         "transaction" => [
    //             "amount" => 5000009,
    //             "country" => "TZ",
    //             "currency" => "TZS",
    //             "id" => "SOLOCODES113"
    //         ]
    //     ];

    //     Log::debug("== AIRTEL Payload ==");
    //     Log::debug($payload);

    //     //API URL
    //     $API_URL = 'https://openapiuat.airtel.africa/merchant/v1/payments/';

    //     //push payload => NMB Direct Debits
    //     $response = Http::withHeaders($headers)->post($API_URL, $payload);

    //     Log::debug('== AIRTEL MandateRegistration Response ==');
    //     Log::debug($response);

    //     return $response;
    // }

    //disbursment api
    // public function disbursment(Request $request)
    // {
    //     $AUTH_BEARER_TOKEN = $this->auth->generate();

    //     //headers
    //     $headers = [
    //         'Accept' => '*/*',
    //         'Content-Type' => 'application/json',
    //         'X-Country' => 'TZ',
    //         'X-Currency' => 'TZS',
    //         'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN,
    //     ];

    //     //create payload
    //     $payload = array(
    //         'payee' => array(
    //             "currency" => "TZS",
    //             "msisdn" => "785670229",
    //             "name" => "JOSE"
    //         ),
    //         "reference" => "Testing transaction",
    //         'pin' => 'hus/I3gBrEAaGHhW34UBB/mOsQSnS4fzNZCDOwI9ciDHJ/RuOtwN+ZoJc31TrHVkl8pKjh4Gf4ENjcXHy8gFmU2g4NtV0gG9Q5oBhktupBPhcqjdVhvORMxZELtmu9fpZ1vivJ+HzUiaiBPUFQrwBkHUhG8KO4m/CXhCE2IVErg=',
    //         'transaction' => array(
    //             'amount' => 1000,
    //             'id' => 'SOLOCODE6930037',
    //             'type' => 'B2C',
    //         )
    //     );

    //     Log::debug("== AIRTEL Disbursment Payload ==");
    //     Log::debug($payload);

    //     //API URL
    //     //$API_URL = 'https://openapiuat.airtel.africa/standard/v1/disbursements/';
    //     $API_URL = 'https://openapiuat.airtel.africa/standard/v2/disbursements/';

    //     //push payload => NMB Direct Debits
    //     $response = Http::withHeaders($headers)->post($API_URL, $payload);

    //     Log::debug('== AIRTEL Disbursment Response ==');
    //     Log::debug($response);

    //     return $response;
    // }

    //create mandate
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
        ];

        //create payload
        $payload = [
            "payer" => [
                "msisdn" => "685466727",
                "wallet_types" => ["NORMAL"]
            ],
            "txn" => [
                "amount" => 1000,
                "total_amount" => 12000,
                "total_number_of_payments" => 12,
                "start_date" => "2023-04-30",
                "end_date" => "2024-04-31",
                "frequency" =>  "MONTHLY"

            ]
        ];

        Log::debug("== AIRTEL Mandate Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = 'https://openapiuat.airtel.africa/pc/{product}/v1/consent';

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== AIRTEL MandateRegistration Response ==');
        Log::debug($response);

        return $response;
    }

    //query mandate status
    public function query_status(Request $request)
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

        //API URL
        $API_URL = 'https://openapiuat.airtel.africa/pc/{product}/v1/consent/CON1679991960033';

        //push payload => Airtel Direct Debits
        $response = Http::withHeaders($headers)->get($API_URL);

        Log::debug('== AIRTEL MandateStatus Response ==');
        Log::debug($response);

        return $response;
    }

    //cancel mandate
    public function cancel(Request $request)
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
        $payload = [];

        Log::debug("== AIRTEL Cancel Mandate Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = 'https://openapiuat.airtel.africa/pc/{product}/v1/consent/CON1679991960033/stop';

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== AIRTEL Cancel Mandate Response ==');
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
