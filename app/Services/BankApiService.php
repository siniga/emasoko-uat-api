<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BankApiService
{
    public function __construct()
    {
    }

    //corporate registration
    public function corporateRegistrationRequest($data)
    {
    }

    //corporate status
    public function corporateStatusRequest($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = "";

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
        $AUTH_BEARER_TOKEN = "";

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
        Log::debug('Collection Create Request');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        // $response = Http::withHeaders($headers)->post($API_URL, $payload);

        // //logging response
        // Log::debug('OTPVerify Response =>' . $response);

        return 200;
    }
}
