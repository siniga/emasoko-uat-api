<?php

namespace App\Http\Controllers\Api\Yas;

use App\Classes\BearerToken;
use App\Http\Controllers\Controller;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Services\DeductionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class MandateController extends Controller
{
    //defined variables
    private $tokenUrl;
    private $clientId;
    private $clientSecret;
    private $auth;

    public function __construct(protected DeductionService $deductionService)
    {
        $tokenUrl =  "https://jumena-pay.tech-vault.com/oauth/token";
        $clientId = "sk_test_NE6vb9DwC9tSAf72ECIzO3e48rvkjhrm"; //env('YAS_CLIENT_ID');
        $clientSecret = "sk_0XUaFB-vswZxMOYryVe_ldM_--6A_rQ_RCdn0JSX1AO3wWbEuzOPDLVffqTL4TON"; //env('YAS_CLIENT_SECRET');
        $this->auth = new BearerToken($clientId, $clientSecret, $tokenUrl);
    }

    //push mandate to CRDB
    public function create(Request $request)
    {
        //predefined variables
        $phone = $request->phone;
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $totalAmount = $request->totalAmount;
        $installmentAmount = $request->installmentAmount;
        $periodicity = $request->periodicity;

        $requestId = Str::uuid()->toString();

        //token
        $bearerToken = $this->auth->generate();
        Log::debug("== Bearer Token ==");
        Log::debug($bearerToken);

        //headers
        $headers = [
            'Authorization' => 'Bearer ' . $bearerToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Idempotency-Key' => $requestId
        ];

        //create payload
        $payload = [
            "orderId" => "SOLO-1702202626166",
            "customerMsisdn" => "255658123898",
            "amount" => 1500
        ];

        Log::debug("== YAS Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = "https://jumena-pay.tech-vault.com/v1/payment-sessions";

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== YAS MandateRegistration Response ==');
        Log::debug($response);

        return $response;
    }

    //cancellation
    public function cancel(Request $request) {}

    //callback function from CRDB
    public function callback(Request $request)
    {
        //logging response
        Log::debug('== YAS Callback Response ==');
        Log::debug($request->all());

        // response
        return response()->json([
            'status' => true,
            'message' => "Callback received",
        ], 200);
    }
}
