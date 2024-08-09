<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Classes\Authentication;
use Str;

class OtpController extends Controller
{
    private $auth;

    function __construct()
    {
        $this->auth = new Authentication();
    }

    public function create(Request $request)
    {
        //validation 

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //create data
        $data = [
            "caseReference" => "0011432",
            "accountNumber" => "62102402432",
            "processName" => "Test JWT Tokens Code",
            "makerName" => "ETest"
        ];

        //create payload
        $payload = [
            "user" => "EMakato",
            'correlationId' => "b30d3134-5624-4391-9f49-1b7a776d69wkd",
            "targetService" => "OTPCreate",
            'status' => "Create",
            "data" => $data
        ];

        //API URL
        $API_URL = "https://onlinebankinguatuat.nmbbank.co.tz:8443/Services/Sybrin.Authentication.Gateway/SybrinAuthenticationGateway";

        //headers
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$AUTH_BEARER_TOKEN
        ];

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        if ($response->status() == 200) {
            $data = $response->json();

            //send response to eMakato
            return response()->json([
                'status' => "Success",
                "caseReference" => "0011432",
                'response' => [
                    'responseCode' => 240,
                    'responseDescription' => "OTP Succesfully Generated"
                ]
            ], 201);
        } else {
            $data = $response->json();
            Log::debug("Error code =>" . $response->status());
            Log::debug($data);

            //send response to eMakato
            return response()->json([
                'status' => "Fail",
                "caseReference" => "0011432",
                'response' => [
                    'responseCode' => 500,
                    'responseDescription' => "Internal Server Error"
                ]
            ], 500);
        }
    }

    public function verify(Request $request)
    {
        //validation 

        //create authentication token
        $AUTH_BEARER_TOKEN = $request->post('AUTH_TOKEN');
        Log::debug("== My TOKEN ==");
        Log::debug($AUTH_BEARER_TOKEN);

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "OTPVerify",
            "status" => "Verify",
            "data" => [
                "caseReference" => "19 07 2024 0006",
                "processName" => "EMakato Mandate Registration",
                "otpPin" => "NR4TPT"
            ]
        ];

        //API URL
        $API_URL = "https://onlinebankinguatuat.nmbbank.co.tz:8443/Services/Sybrin.Authentication.Gateway/SybrinAuthenticationGateway";

        //headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$AUTH_BEARER_TOKEN];

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug("Response");
        Log::debug($response->status());
        Log::debug($response);

        //send response to eMakato
        return response()->json($response->json());

        // if ($response->status() == 200) {
        //     $data = $response->json();

        //     //check status via response code = 230 or 239 to modify response

        //     //send response to eMakato
        //     return response()->json([
        //         'status' => "Success",
        //         "caseReference" => "CO202402070803000005",
        //         'response' => [
        //             'responseCode' => 210,
        //             'responseDescription' => "OTP Valid"
        //         ]
        //     ], 201);
        // } else {
        //     $data = $response->json();
        //     Log::debug("Error code =>" . $response->status());
        //     Log::debug($data);

        //     //send response to eMakato
        //     return response()->json([
        //         'status' => "Fail",
        //         "caseReference" => "CO202402070803000005",
        //         'response' => [
        //             'responseCode' => 500,
        //             'responseDescription' => "Internal Server Error"
        //         ]
        //     ], 500);
        // }
    }
}
