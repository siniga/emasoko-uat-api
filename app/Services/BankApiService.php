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

    //Resend OTP
    public function resendOtpRequest($data)
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
}
