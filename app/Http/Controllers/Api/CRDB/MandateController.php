<?php

namespace App\Http\Controllers\Api\CRDB;

use App\Classes\Checksum;
use App\Http\Controllers\Controller;
use App\Models\DebitOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MandateController extends Controller
{
    private $auth;

    public function __construct()
    {
        $this->auth = new Checksum();
    }

    //push mandate to CRDB
    public function create(Request $request)
    {
        //predefined variables
        $debitAccount = $request->debitAccount;
        $phone = $request->phone;
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $totalAmount = $request->totalAmount;
        $installmentAmount = $request->installmentAmount;
        $periodicity = $request->periodicity;
        $frequency = $request->frequency;

        //validate mandatory fields
        $partnerId = env('CRDB_PARTNER_ID');
        $requestId = Str::uuid()->toString();

        //data and salt
        $data = $requestId . substr($debitAccount, -4) . $partnerId;
        $salt = env('CRDB_SALT');
        
        //create checksum
        $checksum = $this->auth->generateChecksum($data, $salt);

        Log::debug("== checksum ==");
        Log::debug($checksum);

        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //headers
        $headers = [
            'Channel' => env('CRDB_CHANNEL_ID'),
            'AppId' => env('CRDB_APP_ID'),
            'AppVersion' => '1.0.0',
            "RequestId" => $requestId,
            'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        Log::debug("== Headers ==");
        Log::debug($headers);

        //create payload
        $payload = [
            "PartnerId" =>  $partnerId,
            "RequestId" => $requestId,
            'Checksum' => $checksum,
            "PhoneNumber" =>  $phone,
            "DebitAccount" =>  $debitAccount,
            "TotalAmount" =>  $totalAmount,
            "InstallmentAmount" => $installmentAmount,
            "StartDate" => $startDate,
            "EndDate" => $endDate,
            "FrequencyType" => $periodicity,
            "Frequency" => $frequency,
            "BillDetails" => "Recurring payment"
        ];

        Log::debug("== CRDB Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = env('CRDB_API_URL');

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== CRDB MandateRegistration Response ==');
        Log::debug($response);

        return $response;
    }

    //callback function from CRDB
    public function callback(Request $request)
    {
        //logging response
        Log::debug('== CRDB Callback Response ==');
        Log::debug($request->all());

        //Log::channel('APILogs')->Info('CRDB Callback Response:' . json_encode($request->all(),true));

        // check for valid callback type
        $callback_type = $request->input('callbackType');

        if ($callback_type == "REQUEST_STATUS") {
            //query debit order by reference id
            $debit_order = DebitOrder::where('crdb_reference', '=', $request['data']['reference'])->first();

            //check status
            if (strtolower($request['data']['status']) == "approved") {
                $debit_order->approved = 1;
                $debit_order->status = 240; //approved
            } else if (strtolower($request['data']['status']) == "cancelled") {
                $debit_order->status = 501;
            } else {
                $debit_order->status = 501;
            }
            $debit_order->save();
        } else if ($callback_type == "PAYMENT_STATUS") {
            //update installment amount
        }
    }
    
}
