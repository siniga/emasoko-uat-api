<?php

namespace App\Http\Controllers\Api\Airtel;

use App\Classes\AirtelToken;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CollectionController extends Controller
{
    private $auth;

    public function __construct()
    {
        $this->auth = new AirtelToken();
    }

    //collection
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
            "consent_number" => "CON1679991960033",
            "payer" => [
                "wallet_type" => "NORMAL"
            ],
            "transaction" => [
                "id" =>  "RAKESH-pinless155",
                "amount" => "1000"
            ]
        ];

        Log::debug("== AIRTEL Collection Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = 'https://openapiuat.airtel.africa/pc/{product}/v1/payment';

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== AIRTEL Collection Response ==');
        Log::debug($response);

        return $response;
    }

    public function inquiry()
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

        Log::debug("== AIRTEL Collection Inquiry ==");
        //API URL
        $API_URL = 'https://openapiuat.airtel.africa/pc/{product}/v1/payment/RAKESH-pinless155';

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->get($API_URL);

        Log::debug('== AIRTEL Collection Inquiry Response ==');
        Log::debug($response);

        return $response;
    }

    public function refund() {}


    public function refund_inquiry(Request $request)
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
            "consent_number" => "CON1679991960033",

            "transaction" => [
                "id" =>  "RAKESH-pinless155",
            ]
        ];

        Log::debug("== AIRTEL Refund Collection Inquiry Payload ==");
        Log::debug($payload);
        //API URL
        $API_URL = 'https://openapiuat.airtel.africa/pc/{product}/v1/payment/RAKESH-pinless155/refund';

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->get($API_URL);

        Log::debug('== AIRTEL Refund Collection Inquiry Response ==');
        Log::debug($response);

        return $response;
    }

    public function recovery(Request $request)
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
            "consent_number" => "CON1694162665843",
            "player" => [
                "wallet_type" =>  "NORMAL",
            ],
            "transaction" => [
                "id" =>  "RAKESH-pinless-recovery-6",
                "amount" => '3000',
            ]
        ];

        Log::debug("== AIRTEL Collection Recovery Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = 'https://openapiuat.airtel.africa/recovery/v1/payment';

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== AIRTEL Collection Recovery Response ==');
        Log::debug($response);

        return $response;
    }

    public function recovery_inquiry(Request $request)
    {
        $AUTH_BEARER_TOKEN = $this->auth->generate();

        //headers
        $headers = [
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
            'X-Country' => 'TZ',
            'X-Currency' => 'TZS',
            'Authorization' => 'obnF5rHbi6clqwKPpoBHSytQSADxmmOf',
        ];

        //create payload
        $payload = [];

        //API URL
        $API_URL = 'https://openapiuat.airtel.africa/recovery/v1/consent/CON1679916462568/payment/RAKESH-pinless-8';

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== AIRTEL Refund Collection Response ==');
        Log::debug($response);

        return $response;
    }

    public function recovery_refund(Request $request)
    {
        $AUTH_BEARER_TOKEN = $this->auth->generate();

        //headers
        $headers = [
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
            'X-Country' => 'TZ',
            'X-Currency' => 'TZS',
            'Authorization' => 'obnF5rHbi6clqwKPpoBHSytQSADxmmOf',
        ];

        //create payload
        $payload = [];

        //API URL
        $API_URL = 'https://openapiuat.airtel.africa/recovery/v1/refund';

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== AIRTEL Refund Collection Response ==');
        Log::debug($response);

        return $response;
    }
}
