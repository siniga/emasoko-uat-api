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
        $tokenUrl =  config('services.jumena.token_url');
        $clientId = config('services.jumena.client_id');
        $clientSecret = config('services.jumena.client_secret');
        $this->auth = new BearerToken(null, $clientId, $clientSecret, $tokenUrl);
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

        //request id
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
            "orderId" => (string) $request->input('orderId', 'SOLO-' . now()->format('YmdHis')),
            "customerMsisdn" => (string) $request->input('phone'),
            "amount" => (float) $request->input('installmentAmount', 0),
        ];

        Log::debug("== YAS Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = config('services.jumena.payment_session_url');

        if (!$API_URL) {
            return response()->json([
                'status' => false,
                'message' => 'JUMENA payment session URL is not configured',
            ], 500);
        }

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
