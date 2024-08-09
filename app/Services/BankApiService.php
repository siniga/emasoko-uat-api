<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Classes\Authentication;
use App\Models\CollectionBatch;

class BankApiService
{
    private $auth;

    public function __construct()
    {
        $this->auth = new Authentication();
    }

    //corporate registration
    public function corporateRegistrationRequest($data)
    {
    }

    //corporate status
    public function corporateStatusRequest($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "CorporateStatus",
            "status" => "Check",
            "data" => $data
        ];

        //Create Payload
        Log::debug('Corporate Ststus Request');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        //$response = Http::withHeaders($headers)->post($API_URL, $payload);

        //logging response
        //Log::debug('OTPCreate Response =>' . $response);

        return 200;
    }

    //mandate registartion
    public function mandateRegistrationRequest($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = "";

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "MandateStatus",
            "status" => "Check",
            "data" => $data
        ];

        //Create Payload
        Log::debug('Mandate Status Request');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        // $response = Http::withHeaders($headers)->post($API_URL, $payload);

        // //logging response
        // Log::debug('OTPVerify Response =>' . $response);

        return 200;
    }

    //mandate cancellation 
    public function mandateStatusRequest($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = "";

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "MandateStatus",
            "status" => "Check",
            "data" => $data
        ];

        //Create Payload
        Log::debug('Mandate Status Request');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        // $response = Http::withHeaders($headers)->post($API_URL, $payload);

        // //logging response
        // Log::debug('OTPVerify Response =>' . $response);

        return 200;
    }

    //mandate cancellation 
    public function mandateCancellationRequest($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = "";

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "MandateCancellation",
            "status" => "Create",
            "data" => $data
        ];

        //Create Payload
        Log::debug('Mandate Cancellation Request');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        // $response = Http::withHeaders($headers)->post($API_URL, $payload);

        // //logging response
        // Log::debug('OTPVerify Response =>' . $response);

        return 200;
    }

    //create Otp 
    public function createOtpRequest($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = "";

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "OTPCreate",
            "status" => "Create",
            "data" => $data
        ];

        //Create Payload
        Log::debug('OTPCreate Request');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        //$response = Http::withHeaders($headers)->post($API_URL, $payload);

        //logging response
        //Log::debug('OTPCreate Response =>' . $response);

        return 200;
    }

    //verify OTP
    public function verifyOtpRequest($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = "";

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "OTPVerify",
            "status" => "Verify",
            "data" => $data
        ];

        //Create Payload
        Log::debug('OTPVerify Request');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        // $response = Http::withHeaders($headers)->post($API_URL, $payload);

        // //logging response
        // Log::debug('OTPVerify Response =>' . $response);

        return 200;
    }

    //send collection deduction
    public function collectionCreate($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "CollectionCreate",
            "status" => "Create",
            "data" => $data
        ];

        //Create Payload
        Log::debug('== CollectionCreate Request ==');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update collection batch 
        $collection_batch = CollectionBatch::where('collectionReference', '=', $payload['data']['collectionReference'])->first();
        $collection_batch->http_response_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== CollectionCreate Response ==');
        Log::debug($response);

        if ($response['status'] == "Success" || $response['status'] == "Fail") {
            $collection_batch->status = $response['data']['response'][0]['responseCode'];
            $collection_batch->http_response_code = $response['data']['response'][0]['responseCode'];
            $collection_batch->remarks = $response['data']['response'][0]['responseDescription'];
        }
        $collection_batch->save();

        return 200;
    }
}
