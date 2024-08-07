<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Classes\Authentication;
use Str;

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
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();
        Log::debug("== My TOKEN ==");
        Log::debug($AUTH_BEARER_TOKEN);

        return $AUTH_BEARER_TOKEN;

        exit();

        //create data
        $data = [
            "corporateReference" => $request->post('CorporateReference'),
            "name" => $request->post('name'),
            "corporateShortCode" => $request->post('corporateShortCode'),
            
            "clientCode" => $request->post('clientCode'),
            "customerCode" => $request->post('customerCode'),
            "accountName" => $request->post("accountName"),
            "primaryAccount" => $request->post('primaryAccount'),
            "branchSortCode" => "608",
            "currency" => "TZS",

            "accountStatus" => null,
            "address1" => "MBEYA",
            "address2" => "IBABA ILEJE MBEYA",
            "address3" => "IBABA ILEJE MBEYA",
            "phoneNumber" => $request->post('phoneNumber'),
            "email" => $request->post('email'),
            "secondaryMobile" => $request->post('phoneNumber'),
            "secondaryEmail" => $request->post('email'),
            "suffix" => "Large",
            "deductionsActive" => "false",
            "description" => "Coding company primary located in Tanzania",

            "bookedBalance" => 369791.125,
            "creditLimit" => 0,
            "debitLimit" => 0,
            "corporateLimit" => 50000000,
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
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "CorporateRegistration",
            "status" => "Create",
            "data" => $data
        ];

        Log::debug("payload");
        Log::debug($payload);

        //API URL
        $API_URL = "https://onlinebankinguatuat.nmbbank.co.tz:8443/Services/Sybrin.Authentication.Gateway/SybrinAuthenticationGateway";

        //headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$AUTH_BEARER_TOKEN];

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug("Response JSON");
        Log::debug($response->status());
        Log::debug($response);

        //send response to eMakato
        return response()->json($response->json());

        // if ($response->status() == 200) {
        //     $data = $response->json();

        //     //send response to eMakato
        //     return response()->json([
        //         'status' => "Success",
        //         'corporateReference' => "CO202402070803000007",
        //         'response' => [
        //             'responseCode' => 201,
        //             'responseDescription' => "Corporate Registration Complete"
        //         ]
        //     ], 201);
        // } else {
        //     $data = $response->json();
        //     Log::debug("Error code =>" . $response->status());
        //     Log::debug($data);

        //     //send response to eMakato
        //     return response()->json([
        //         'status' => "Fail",
        //         'corporateReference' => "CO202402070803000007",
        //         'response' => [
        //             'responseCode' => 500,
        //             'responseDescription' => "Internal Server Error"
        //         ]
        //     ], 500);
        // }
    }

    public function status(Request $request)
    {
        //validation 

        //create authentication token
        //$AUTH_BEARER_TOKEN = $this->auth->generateToken();
        $AUTH_BEARER_TOKEN = $request->post('AUTH_TOKEN');
        Log::debug("== My TOKEN ==");
        Log::debug($AUTH_BEARER_TOKEN);

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "CorporateStatus",
            "status" =>  "Check",
            "data" => [
                "corporateReference" => $request->post('CorporateReference')
            ]
        ];

        Log::debug("payload");
        Log::debug($payload);

        //API URL
        $API_URL = "https://onlinebankinguatuat.nmbbank.co.tz:8443/Services/Sybrin.Authentication.Gateway/SybrinAuthenticationGateway";

        //headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$AUTH_BEARER_TOKEN];

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug("Response JSON");
        Log::debug($response->status());
        Log::debug($response);

        //send response to eMakato
        return response()->json($response->json());

        // if ($response->status() == 200) {
        //     $data = $response->json();

        //     //send response to eMakato
        //     return response()->json([
        //         'status' => "Success",
        //         'corporateReference' => "CO202402070803000007",
        //         'response' => [
        //             'responseCode' => 240,
        //             'responseDescription' => "Case Complete"
        //         ]
        //     ], 201);
        // } else {
        //     $data = $response->json();
        //     Log::debug("Error code =>" . $response->status());
        //     Log::debug($data);

        //     //send response to eMakato
        //     return response()->json([
        //         'status' => "Fail",
        //         'corporateReference' => "CO202402070803000007",
        //         'response' => [
        //             'responseCode' => 500,
        //             'responseDescription' => "Internal Server Error"
        //         ]
        //     ], 500);
        // }
    }
}
