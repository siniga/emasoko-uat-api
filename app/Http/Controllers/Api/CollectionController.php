<?php

namespace App\Http\Controllers\Api;

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
        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //create data
        $data = [
            "collectionReference" => "12189",
            "corporateReference" => "CO202409080803000041",
            "selectedPostingDate" => null,
            "createdBy" => "Steven.Gerald",
            "verifiedBy" => "Victor.Mpendazoe",
            "createdDate" => "2024-08-10 08:00:00",
            "verifyDate" => "2024-08-010 09:00:00",
            "file" => [
                "fileName" => "Payment_Collection3",
                "fileType" => "CSV",
                "fileData" => "U0VSSUFMIE5vLjtCQU5LIENPREU7QlJBTkNIIE5BTUU7RU1QTE9ZRUUgTlVNQkVSO0NVU1RPTUVSIE5BTUU7QUNDT1VOVCBOTzsgQU1PVU5UIAoxOzE2O05NQjt4eXo7VGVzdCBUZXN0OzMwMTAyNDAwMDg1OzIwCjE7MTY7Tk1CO3h5ejtUZXN0IFRlc3Q7MzAxMDI0MDAwODU7MjA="
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
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$AUTH_BEARER_TOKEN];

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //send response to eMakato
        return response()->json($response->json());
    }

    public function status(Request $request)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => "b30d3134-5624-4391-9f49-1b7a776d7778",
            "targetService" => "CollectionStatus",
            "status" =>  "Check",
            "data" => [
                "collectionReference" => "CO202402070803000001"
            ]
        ];

        //API URL
        $API_URL = "https://onlinebankinguatuat.nmbbank.co.tz:8443/Services/Sybrin.Authentication.Gateway/SybrinAuthenticationGateway";

        //headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$AUTH_BEARER_TOKEN];

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //send response to eMakato
        return response()->json($response->json());
    }
}
