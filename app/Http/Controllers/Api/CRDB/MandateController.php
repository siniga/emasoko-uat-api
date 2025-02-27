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
        $StartDate = $request->StartDate;
        $EndDate = $request->EndDate;

        // $debitAccount = "0152418973800";
        // $phone = "255711711125";
        $partnerId = "7c0e38cf-1f74-4fc1-83d0-abd4afb5b9d9";
        $requestId = Str::uuid()->toString();

        //data and salt
        $data = $requestId . substr($debitAccount, -4) . $partnerId;
        $salt = "dPQ11iVNGI2aLnCUGdQ3LQA2tCx00WzTxj6zPsdLK3vvR34n5J9s4U4kwzMguTTlahYY66yr6ieqbo7CfGZsVOlt8xKZr+Jon7jc599v9Zkw94ZIpI6Y3YmgXwLZql9nGsgWyA==";
        //create checksum
        $checksum = $this->auth->generateChecksum($data, $salt);


        Log::debug("== checksum ==");
        Log::debug($checksum);

        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //headers
        $headers = [
            'Channel' => 'IB',
            'AppId' => 'INTERNET',
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
            "TotalAmount" =>  "1000",
            "InstallmentAmount" => "100",
            "StartDate" => $StartDate,
            "EndDate" => $EndDate,
            // "StartDate" => "20-01-2025",
            // "EndDate" => "10-10-2025",
            "FrequencyType" => "DAILY",
            "Frequency" => "1",
            "BillDetails" => "Recurring payment"
        ];

        Log::debug("== Payload ==");
        Log::debug($payload);

        //API URL
        $API_URL = env('CRDB_API_URL');

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        Log::debug('== MandateRegistration Response ==');
        Log::debug($response);

        return $response;
    }

    //callback function from CRDB
    public function callback(Request $request)
    {
        //logging response
        Log::debug('== CRDB Callback Response ==');
        Log::debug($request->all());

        //\Log::channel('APILogs')->Info('CRDB Callback Response:' . json_encode($request->all(),true));

        return $request['callbackType'];

        // {
        //     "callbackType": "PAYMENT_STATUS",
        //     "requestId": "8feb5f39-9955-43cb-8548-caefddb3848d",
        //     "data": {
        //         "reference": 10020,
        //         "phoneNumber": "255752059910",
        //         "accountNumber": "0152202249000",
        //         "transferResponse": "success",
        //         "transactionDateTime": "2023-12-01T05:56:15.649142047"
        //     }
        // }
        
         
        
        // {
        //     "callbackType": "REQUEST_STATUS",
        //     "requestId": "8feb5f39-9955-43cb-8548-caefddb3848d",
        //     "data": {
        //         "reference": 10020,
        //         "status": "APPROVED"
        //     }
        // }

        if ($request['callbackType'] == "REQUEST_STATUS") {
            //query debit order by reference id
            $debit_order = DebitOrder::where('crdb_reference', '=', $request['data']['reference'])->first();

            //check status
            if (strtolower($request['data']['status']) == "approved") {
                $debit_order->approved = 1;
                $debit_order->status = 202;
            } else if (strtolower($request['data']['status']) == "cancelled") {
                $debit_order->status = 501;
            } else {
                $debit_order->status = 501;
            }
            $debit_order->save();
        } else if ($request['callbackType'] == "PAYMENT_STATUS") {
            //update installment amount
        }
    }
    
}
