<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Classes\Authentication;

class CorporateController extends Controller
{
    private $auth;

    function __construct()
    {
        $this->auth = new Authentication();
    }

    public function registration(Request $request)
    {
        //validation 

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->create_token();

        //create data
        $data = [
            "CorporateReference" => "CO202402070803000001",
            "name" => "SYBRIN",
            "corporateShortCode" => "SYB",
            "clientCode" => "SY",
            "customerCode" => "000000822",
            "accountName" => "SOPHIA SALASITA MWASENGA",
            "primaryAccount" => "60802400064",
            "branchSortCode" => "608",
            "currency" => "TZS",
            "accountStatus" => null,
            "address1" => "MBEYA",
            "address2" => "IBABA ILEJE MBEYA",
            "address3" => "IBABA ILEJE MBEYA",
            "phoneNumber" => "1234567890",
            "email" => "Sybrin@Sybrin.com",
            "secondaryMobile" => "1234567890",
            "secondaryEmail" => "test@sybrin.com",
            "suffix" => "Large",
            "deductionsActive" => "false",
            "description" => "Coding company primary located in South Africa",
            "bookedBalance" => 369791.125,
            "creditLimit" => 0,
            "debitLimit" => 0,
            "coporateLimit" => 500000000,
            "isOverallLimit" => true,
            "defaultAmount" => 500,
            "defaultPercentage" => 0,
            "createdBy" => "Steven.Gerald",
            "verifiedBy" => "Victor.Mpendazoe",
            "createdDate" => "2024-02-08 12:00:00",
            "verifyDate" => "2024-02-08 13:00:00"
        ];

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => "b30d3134-5624-4391-9f49-1b7a776d692e",
            "targetService" => "CorporateRegistration",
            "status" => "Create",
            "data" => $data
        ];

        //API URL
        $API_URL = "https://onlinebankinguatuat.nmbbank.co.tz:8443/Services/Sybrin.Authentication.Gateway/SybrinAuthenticationGateway";

        //headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$AUTH_BEARER_TOKEN];

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        if ($response->status() == 200) {
            $data = $response->json();

            //send response to eMakato
            return response()->json([
                'status' => "Success",
                'corporateReference' => "CO202402070803000001",
                'response' => [
                    'responseCode' => 201,
                    'responseDescription' => "Corporate Registration Complete"
                ]
            ], 201);
        } else {
            $data = $response->json();
            Log::debug("Error code =>" . $response->status());
            Log::debug($data);

            //send response to eMakato
            return response()->json([
                'status' => "Fail",
                'corporateReference' => "CO202402070803000001",
                'response' => [
                    'responseCode' => 500,
                    'responseDescription' => "Internal Server Error"
                ]
            ], 500);
        }
    }

    public function status(Request $request)
    {
        //validation 

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->create_token();

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => "b30d3134-5624-4391-9f49-1b7a776d692e",
            "targetService" => "CorporateStatus",
            "status" =>  "Check",
            "data" => [
                "corporateReference" => "CO202402070803000001"
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

            //send response to eMakato
            return response()->json([
                'status' => "Success",
                'corporateReference' => "CO202402070803000001",
                'response' => [
                    'responseCode' => 240,
                    'responseDescription' => "Case Complete"
                ]
            ], 201);
        } else {
            $data = $response->json();
            Log::debug("Error code =>" . $response->status());
            Log::debug($data);

            //send response to eMakato
            return response()->json([
                'status' => "Fail",
                'corporateReference' => "CO202402070803000001",
                'response' => [
                    'responseCode' => 500,
                    'responseDescription' => "Internal Server Error"
                ]
            ], 500);
        }
    }
}
