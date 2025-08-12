<?php

namespace App\Http\Controllers\Api\NMB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use App\Classes\Authentication;
use Illuminate\Support\Str;

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
            "mandateReferenceNumber" => "141120241411",
            "mandateStartDate" => "2024-11-20T22:00:00.000",
            "mandateEndDate" => "2024-12-09T22:00:00.000Z", 
            "directDebitType" => "MONTHLY",
            "debitAccountHolderName" => "YYYY",
            "debitAccountNumber" => '31202402439',
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
            "maxAmount" => 2000,
            "minAmount" => 1000,
            "createdBy" => "Steven.Gerald",
            "verifiedBy" => "Victor.Mpendazoe",
            "createdDate" => "2024-10-30 10:00:00",
            "verifyDate" => "2024-10-30 11:00:00",
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
        $API_URL = env('API_URL');

        //headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$AUTH_BEARER_TOKEN];

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);
        Log::debug("== response ==");
        Log::debug($response);  
        
        return response()->json($response->json());
    }

    public function status(Request $request)
    {
        //validation 

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "MandateStatus",
            "status" =>  "Check",
            "data" => [
                "mandateReferenceNumber" => "141120241407"
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
    }

    public function cancellation(Request $request)
    {
        //validation 

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //create data
        $data = [
            "mandateReferenceNumber" => "141120241405",
            "corporateReference" => "CO202402070803000014",
            "cancelReason" => "Failed to comply",
            "createdBy" => "Steven.Gerald",
            "verifiedBy" => "Victor.Mpendazoe",
            "createdDate" => "2024-02-08 12:00:00",
            "verifyDate" => "2024-02-08 13:00:00"
        ];

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
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
    }
}
