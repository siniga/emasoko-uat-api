<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Classes\Authentication;

class MandateController extends Controller
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
            "mandateReferenceNumber" => "12145",
            "mandateStartDate" => "2024-07-28T22:00:00.000",
            "mandateEndDate" => "2024-08-02T22:00:00.000Z",
            "directDebitType" => "Variable",
            "debitAccountHolderName" => "YYYY",
            "debitAccountNumber" => "20910020198",
            "debitAccountBankCode" => "016",
            "debitAccountHolderAddress1" => "IBILILO",
            "debitAccountHolderAddress2" => "MBEYA",
            "debitAccountHolderAddress3" => "IBILILO",
            "corporateReference" => "CO202402070803000018",
            "creditAccountNumber" => "50510034031",
            "creditAccountName" => "MyBiggieConglomerate",
            "creditAccountShortName" => "SYB",
            "beneficiaryBank" => "NMB",
            "accountStatus" => "Account Is Valid",
            "creditAccountAddressLine1" => "MARA",
            "creditAccountAddressLine2" => "NYANDUGA",
            "amount" => 0,
            "maxAmount" => 100,
            "minAmount" => 15,
            "createdBy" => "Steven.Gerald",
            "verifiedBy" => "Victor.Mpendazoe",
            "createdDate" => "2024-02-08 12:00:00",
            "verifyDate" => "2024-02-08 13:00:00",
            "file" => [
                "fileName" => "Customer Slip",
                "fileType" => "pdf",
                "fileData" => ""
            ]
        ];

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => "b30d3134-5624-4391-9f49-1b7a776d692e",
            "targetService" => "MandateRegistration",
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
                'mandateReferenceNumber' => "CO202402070803000001",
                'response' => [
                    'responseCode' => 201,
                    'responseDescription' => "Mandate Registration Complete"
                ]
            ], 201);
        } else {
            $data = $response->json();
            Log::debug("Error code =>" . $response->status());
            Log::debug($data);

            //send response to eMakato
            return response()->json([
                'status' => "Fail",
                'mandateReferenceNumber' => "CO202402070803000001",
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
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();
        Log::debug("== My TOKEN ==");
        Log::debug($AUTH_BEARER_TOKEN);

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => "b30d3134-5624-4391-9f49-1b7a776d692e",
            "targetService" => "MandateStatus",
            "status" =>  "Check",
            "data" => [
                "mandateReferenceNumber" => "CO202402070803000001"
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
                'mandateReferenceNumber' => "CO202402070803000001",
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
                'mandateReferenceNumber' => "CO202402070803000001",
                'response' => [
                    'responseCode' => 500,
                    'responseDescription' => "Internal Server Error"
                ]
            ], 500);
        }
    }

    public function cancellation(Request $request)
    {
        //validation 

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();
        Log::debug("== My TOKEN ==");
        Log::debug($AUTH_BEARER_TOKEN);

        //create data
        $data = [
            "mandateReferenceNumber" => "12144",
            "corporateReference" => "CO202402070803000001",
            "cancelReason" => "test",
            "createdBy" => "Steven.Gerald",
            "verifiedBy" => "Victor.Mpendazoe",
            "createdDate" => "2024-02-08 12:00:00",
            "verifyDate" => "2024-02-08 13:00:00"
        ];

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => "b30d3134-5624-4391-9f49-1b7a776d692e",
            "targetService" => "MandateCancellation",
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
                'mandateReferenceNumber' => "CO202402070803000001",
                'response' => [
                    'responseCode' => 201,
                    'responseDescription' => "Mandate Cancellation Registration Complete"
                ]
            ], 201);
        } else {
            $data = $response->json();
            Log::debug("Error code =>" . $response->status());
            Log::debug($data);

            //send response to eMakato
            return response()->json([
                'status' => "Fail",
                'mandateReferenceNumber' => "CO202402070803000001",
                'response' => [
                    'responseCode' => 500,
                    'responseDescription' => "Internal Server Error"
                ]
            ], 500);
        }
    }
}
