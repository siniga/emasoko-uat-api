<?php

namespace App\Http\Controllers\Api\v1;

use App\Classes\Authentication;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpController extends Controller
{
    private $auth;

    function __construct()
    {
        //$this->auth = new Authentication();
    }

    public function create(Request $request)
    {
        //validation 
        echo "hello";
        exit();

        //create authentication token

        //create data
        $data = [
            "caseReference" => "0003",
            "accountNumber" => "20910020198",
            "processName" => "Test JWT Tokens Code",
            "makerName" => "ETest"
        ];

        //create payload
        $payload = [
            "user" => "EMakato",
            'correlationId' => "b30d3134-5624-4391-9f49-1b7a776d692e",
            "targetService" => "OTPCreate",
            'status' => "Create",
            "data" => $data
        ];

        //API URL
        $API_URL = "http://nmb-ts-dds-v006:8080/Services/Sybrin.Authentication.Gateway/SybrinAuthenticationGateway";

        //headers
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE3MTgyNjIyOTAsIm5iZiI6MTcxODI2MjI5MC wiZXhwIjoxNzE4MjYyNTkwLCJpc3MiOiJFTWFrYXRvIiwiYXVkIjoiRU1ha2F0byJ9.F0YxGKpcjR4izC0 u3nxx_CuqE1405pJE8-TMplVBn24'
        ];

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        if ($response->status() == 200) {
            $data = $response->json();

            //send response to eMakato
            return response()->json([
                'status' => "Success",
                "caseReference" => "121451",
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
                "caseReference" => "121451",
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

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => "b30d3134-5624-4391-9f49-1b7a776d692e",
            "targetService" => "OTPVerify",
            "status" => "Verify",
            "data" => [
                "caseReference" => "CO202402070803000001",
                "processName" => "MandateApproval",
                "otpPin" => "A5R4"
            ]
        ];

        //API URL
        $API_URL = "";

        //headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer AUTH_BEARER_TOKEN'];

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        if ($response->status() == 200) {
            $data = $response->json();

            //check status via response code = 230 or 239 to modify response

            //send response to eMakato
            return response()->json([
                'status' => "Success",
                "caseReference" => "CO202402070803000001",
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
                "caseReference" => "CO202402070803000001",
                'response' => [
                    'responseCode' => 500,
                    'responseDescription' => "Internal Server Error"
                ]
            ], 500);
        }
    }
}
