<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Classes\Authentication;
use Str;

class MandateController extends Controller
{
    private $auth;

    function __construct()
    {
        $this->auth = new Authentication();
    }

    public function create(Request $request)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //create data
        $data = [
            "mandateReferenceNumber" => "090820241399",
            "mandateStartDate" => "2024-08-30T22:00:00.000",
            "mandateEndDate" => "2024-10-09T22:00:00.000Z", 
            "directDebitType" => "MONTHLY",
            "debitAccountHolderName" => "YYYY",
            "debitAccountNumber" => '32002400690',
            "debitAccountBankCode" => "016",
            "debitAccountHolderAddress1" => "IBILILO",
            "debitAccountHolderAddress2" => "MBEYA",
            "debitAccountHolderAddress3" => "IBILILO",
            "corporateReference" => "CO202402070803000014",
            "creditAccountNumber" => "24610003451",
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
                "fileData" => pdf_to_base64(public_path('assets/uploads/docs/Invoice_EUINTZ24_9765.pdf')),
            ]
        ];

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "MandateRegistration",
            "status" => "Create",
            "data" => $data
        ];

        Log::debug("payload");
        Log::debug($payload);

        //API URL
        $API_URL = "https://onlinebankingapi.nmbbank.co.tz:10443/Services/Sybrin.Authentication.Gateway/SybrinAuthenticationGateway";

        //headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$AUTH_BEARER_TOKEN];

        //push payload => NMB Direct Debits
        $response = Http::withOptions(['verify' => false])->withHeaders($headers)->post($API_URL, $payload);

        Log::debug("Response");
        Log::debug($response->status());
        Log::debug($response);

        //send response to eMakato
        return response()->json($response->json());

        // if ($response->status() == 200) {
        //     $data = $response->json();

        //     //send response to eMakato
        //     return response()->json([
        //         'status' => "Success",
        //         'mandateReferenceNumber' => "CO202402070803000005",
        //         'response' => [
        //             'responseCode' => 201,
        //             'responseDescription' => "Mandate Registration Complete"
        //         ]
        //     ], 201);
        // } else {
        //     $data = $response->json();
        //     Log::debug("Error code =>" . $response->status());
        //     Log::debug($data);

        //     //send response to eMakato
        //     return response()->json([
        //         'status' => "Fail",
        //         'mandateReferenceNumber' => "CO202402070803000005",
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
        $AUTH_BEARER_TOKEN = $request->post('AUTH_TOKEN');
        Log::debug("== My TOKEN ==");
        Log::debug($AUTH_BEARER_TOKEN);

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "MandateStatus",
            "status" =>  "Check",
            "data" => [
                "mandateReferenceNumber" => "090820241384"
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

        //     //send response to eMakato
        //     return response()->json([
        //         'status' => "Success",
        //         'mandateReferenceNumber' => "CO202402070803000005",
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
        //         'mandateReferenceNumber' => "CO202402070803000005",
        //         'response' => [
        //             'responseCode' => 500,
        //             'responseDescription' => "Internal Server Error"
        //         ]
        //     ], 500);
        // }
    }

    public function cancellation(Request $request)
    {
        //validation 

        //create authentication token
        $AUTH_BEARER_TOKEN = $request->post('AUTH_TOKEN');
        Log::debug("== My TOKEN ==");
        Log::debug($AUTH_BEARER_TOKEN);

        //create data
        $data = [
            "mandateReferenceNumber" => "090820241384",
            "corporateReference" => "CO202402070803000014",
            "cancelReason" => "test",
            "createdBy" => "Steven.Gerald",
            "verifiedBy" => "Victor.Mpendazoe",
            "createdDate" => "2024-02-08 12:00:00",
            "verifyDate" => "2024-02-08 13:00:00"
        ];

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => "b30d3134-5624-4391-9f49-1b7a776d69hryyd",
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

        Log::debug("Response");
        Log::debug($response->status());
        Log::debug($response);

        //send response to eMakato
        return response()->json($response->json());

        // if ($response->status() == 200) {
        //     $data = $response->json();

        //     //send response to eMakato
        //     return response()->json([
        //         'status' => "Success",
        //         'mandateReferenceNumber' => "CO202402070803000001",
        //         'response' => [
        //             'responseCode' => 201,
        //             'responseDescription' => "Mandate Cancellation Registration Complete"
        //         ]
        //     ], 201);
        // } else {
        //     $data = $response->json();
        //     Log::debug("Error code =>" . $response->status());
        //     Log::debug($data);

        //     //send response to eMakato
        //     return response()->json([
        //         'status' => "Fail",
        //         'mandateReferenceNumber' => "CO202402070803000001",
        //         'response' => [
        //             'responseCode' => 500,
        //             'responseDescription' => "Internal Server Error"
        //         ]
        //     ], 500);
        // }
    }
}
