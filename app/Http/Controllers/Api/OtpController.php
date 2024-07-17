<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Classes\Authentication;

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
        Log::debug("== My TOKEN ==");
        Log::debug($AUTH_BEARER_TOKEN);

        //create data
        $data = [
            "caseReference" => "001122",
            "accountNumber" => "61702400937",
            "processName" => "Test JWT Tokens Code",
            "makerName" => "ETest"
        ];

        //create payload
        $payload = [
            "user" => "EMakato",
            'correlationId' => "b30d3134-5624-4391-9f49-1b7a776d69ree",
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
                "caseReference" => "CO202402070803000005",
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
                "caseReference" => "CO202402070803000005",
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
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();
        Log::debug("== My TOKEN ==");
        Log::debug($AUTH_BEARER_TOKEN);

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => "b30d3134-5624-4391-9f49-1b7a776d692e",
            "targetService" => "OTPVerify",
            "status" => "Verify",
            "data" => [
                "caseReference" => "CO202402070803000005",
                "processName" => "MandateApproval",
                "otpPin" => "A5999"
            ]
        ];

        //API URL
        $API_URL = "https://onlinebankinguatuat.nmbbank.co.tz:8443/Services/Sybrin.Authentication.Gateway/SybrinAuthenticationGateway";

        //headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$AUTH_BEARER_TOKEN];

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        if ($response->status() == 200) {
            $data = $response->json();

            //check status via response code = 230 or 239 to modify response

            //send response to eMakato
            return response()->json([
                'status' => "Success",
                "caseReference" => "CO202402070803000005",
                'response' => [
                    'responseCode' => 210,
                    'responseDescription' => "OTP Valid"
                ]
            ], 201);
        } else {
            $data = $response->json();
            Log::debug("Error code =>" . $response->status());
            Log::debug($data);

            //send response to eMakato
            return response()->json([
                'status' => "Fail",
                "caseReference" => "CO202402070803000005",
                'response' => [
                    'responseCode' => 500,
                    'responseDescription' => "Internal Server Error"
                ]
            ], 500);
        }
    }
}
