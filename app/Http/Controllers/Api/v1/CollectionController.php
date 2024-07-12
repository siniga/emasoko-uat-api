<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Classes\Authentication;

class CollectionController extends Controller
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
        $BEARER_TOKEN = $this->auth->create_token();

        //create data
        $data = [
            "collectionReference" => "12144",
            "corporateReference" => "CO202402070803000001",
            "selectedPostingDate" => null,
            "createdBy" => "Steven.Gerald",
            "verifiedBy" => "Victor.Mpendazoe",
            "createdDate" => "2024-02-08 12:00:00",
            "verifyDate" => "2024-02-08 13:00:00",
            "file" => [
                "fileName" => "Payment_Collection3",
                "fileType" => "CSV",
                "fileData" => ""
            ]
        ];

        //create payload
        $payload = [
            "user" => "EMakato",
            'correlationId' => "b30d3134-5624-4391-9f49-1b7a776d692e",
            'targetService' => "CollectionCreate",
            'status' => "Create",
            "data" => $data
        ];

        //API URL
        $API_URL = "https://onlinebankinguatuat.nmbbank.co.tz:8443/Services/Sybrin.Authentication.Gateway/SybrinAuthenticationGateway";

        //headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$BEARER_TOKEN];

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        if ($response->status() == 200) {
            $data = $response->json();

            //send response to eMakato
            return response()->json([
                'status' => "Success",
                "corporateReference" => "CO202402070803000001",
                'response' => [
                    'responseCode' => 201,
                    'responseDescription' => "Collection registration Complete"
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
        $BEARER_TOKEN = $this->auth->create_token();

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => "b30d3134-5624-4391-9f49-1b7a776d692e",
            "targetService" => "CollectionStatus",
            "status" =>  "Check",
            "data" => [
                "collectionReference" => "CO202402070803000001"
            ]
        ];

        //API URL
        $API_URL = "https://onlinebankinguatuat.nmbbank.co.tz:8443/Services/Sybrin.Authentication.Gateway/SybrinAuthenticationGateway";

        //headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$BEARER_TOKEN];

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        if ($response->status() == 200) {
            $data = $response->json();

            //check status via response code = 230 or 239 to modify response

            //send response to eMakato
            return response()->json([
                'status' => "Success",
                "collectionReference" => "CO202402070803000001",
                'response' => [
                    'responseCode' => 230,
                    'responseDescription' => "Batch Completed"
                ]
            ], 201);
        } else {
            $data = $response->json();
            Log::debug("Error code =>" . $response->status());
            Log::debug($data);

            //send response to eMakato
            return response()->json([
                'status' => "Fail",
                "collectionReference" => "CO202402070803000001",
                'response' => [
                    'responseCode' => 500,
                    'responseDescription' => "Internal Server Error"
                ]
            ], 500);
        }
    }
}
