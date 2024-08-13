<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Classes\Authentication;
use App\Models\CollectionBatch;
use App\Models\DebitOrder;
use App\Models\OtpRequest;
use App\Models\Partner;
use Exception;

class BankApiService
{
    private $auth;

    public function __construct()
    {
        $this->auth = new Authentication();
    }

    //corporate registration
    public function corporateRegistrationRequest($data)
    {
        //declare code
        $http_code = 200;

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();
        Log::debug("Token : {$AUTH_BEARER_TOKEN}");

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "CorporateRegistration",
            "status" => "Create",
            "data" => $data
        ];

        //Create Payload
        Log::debug('== CorporateRegistration Request == ');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');


        try {
            //response
            $response = Http::withHeaders($headers)->post($API_URL, $payload);

            if ($response) {
                //update the corporate
                $partner = Partner::where('reference', '=', $payload['data']['corporateReference'])->first();
                $partner->http_response_json = $response;

                //decode the response 
                $response = json_decode($response, true);

                //logging response
                Log::debug('== CorporateRegistration Response ==');
                Log::debug($response);

                if ($response['status'] == "Success") {
                    $partner->status = 201; //created

                    //http code
                    $http_code = 201;
                } else if ($response['status'] == "Fail") {
                    $http_code = 403; //Failed

                    if ($response['data']['response'][0]['responseCode'] != 410)
                        $partner->status = 403;
                }

                $partner->http_response_code = $response['data']['response'][0]['responseCode'];
                $partner->remarks = $response['data']['response'][0]['responseDescription'];
                $partner->save();

                return response()->json(['error' => false, 'http_code' => $http_code, 'error_msg' => $response['data']['response'][0]['responseDescription']]);
            } else {
                return response()->json(['error' => true, 'http_code' => 500, 'error_msg' => 'No any response.']);
            }
        } catch (Exception $e) {
            return response()->json(['error' => true, 'http_code' => 500, 'error_msg' => $e]);
        }
    }

    //corporate status
    public function corporateStatusRequest($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "CorporateStatus",
            "status" => "Check",
            "data" => $data
        ];

        //Create Payload
        Log::debug('Corporate Ststus Request');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        //$response = Http::withHeaders($headers)->post($API_URL, $payload);

        //logging response
        //Log::debug('OTPCreate Response =>' . $response);

        return 200;
    }

    //mandate registartion
    public function mandateRegistrationRequest($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = "";

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "MandateStatus",
            "status" => "Check",
            "data" => $data
        ];

        //Create Payload
        Log::debug('Mandate Status Request');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        // $response = Http::withHeaders($headers)->post($API_URL, $payload);

        // //logging response
        // Log::debug('OTPVerify Response =>' . $response);

        return 200;
    }

    //mandate cancellation 
    public function mandateStatusRequest($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = "";

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "MandateStatus",
            "status" => "Check",
            "data" => $data
        ];

        //Create Payload
        Log::debug('Mandate Status Request');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        // $response = Http::withHeaders($headers)->post($API_URL, $payload);

        // //logging response
        // Log::debug('OTPVerify Response =>' . $response);

        return 200;
    }

    //mandate cancellation 
    public function mandateCancellationRequest($data)
    {
        //responce code
        $response_code = 200;

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "MandateCancellation",
            "status" => "Create",
            "data" => $data
        ];

        //Create Payload
        Log::debug('== Mandate Cancellation Request ==');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update the debit order
        $debit_order = DebitOrder::where('reference', '=', $payload['data']['mandateReferenceNumber'])->first();
        $debit_order->http_response_status_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== MandateCancellation Response  ==');
        Log::debug($response);

        if ($response['status'] == "Success") {
            $response_code = 405; //response code

            $debit_order->deleted = 1;
            $debit_order->status = 405;
            $debit_order->cancellation_reason = $payload['data']['cancelReason'];
            $debit_order->http_response_code = $response['data']['response'][0]['responseCode'];
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];
        } else if ($response['status'] == "Fail") {
            $response_code = 500; //response code

            $debit_order->deleted = 0;
            $debit_order->status = 500;
            $debit_order->http_response_code = $response['data']['response'][0]['responseCode'];
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];
        }
        $debit_order->save();

        return $response_code;
    }

    //create Otp 
    public function createOtpRequest($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = "";

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "OTPCreate",
            "status" => "Create",
            "data" => $data
        ];

        //Create Payload
        Log::debug('OTPCreate Request');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        //$response = Http::withHeaders($headers)->post($API_URL, $payload);

        //logging response
        //Log::debug('OTPCreate Response =>' . $response);

        return 200;
    }

    //verify OTP
    public function verifyOtpRequest($data, $account_number, $mandate_reference)
    {
        //responce code
        $response_code = 200;

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "OTPVerify",
            "status" => "Verify",
            "data" => $data
        ];

        //Create Payload
        Log::debug('OTPVerify Request');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update the otp request
        $otp_request = OtpRequest::where(
            [
                'reference' => $payload['data']['caseReference'],
                'account_number' => $account_number,
                'action' => 'verify',
            ]
        )->first();
        $otp_request->http_response_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== OtpVerify Response  ==');
        Log::debug($response);

        if ($response['status'] == "Success") {
            $response_code = 202; //response code

            //update otp response code
            $otp_request->http_response_code = $response['data']['response'][0]['responseCode'];

            //Update mandate status based on response
            $debit_order = DebitOrder::where('reference', '=', $mandate_reference)->first();
            $debit_order->status = 202;
            $debit_order->approved = 'APPROVED';
            $debit_order->save();
        } else if ($response['status'] == "Fail") {
            $response_code = 500; //response code

            //update otp response code
            $otp_request->http_response_code = $response['data']['response'][0]['responseCode'];

            //Update mandate status based on response
            $debit_order = DebitOrder::where('reference', '=', $mandate_reference)->first();
            $debit_order->status = 500;
            $debit_order->approved = 'REJECTED';
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];
            $debit_order->save();
        }
        $otp_request->save(); //save

        return $response_code;
    }

    //send collection deduction
    public function collectionCreate($data)
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "CollectionCreate",
            "status" => "Create",
            "data" => $data
        ];

        //Create Payload
        Log::debug('== CollectionCreate Request ==');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update collection batch 
        $collection_batch = CollectionBatch::where('collectionReference', '=', $payload['data']['collectionReference'])->first();
        $collection_batch->http_response_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== CollectionCreate Response ==');
        Log::debug($response);

        if ($response['status'] == "Success" || $response['status'] == "Fail") {
            $collection_batch->status = $response['data']['response'][0]['responseCode'];
            $collection_batch->http_response_code = $response['data']['response'][0]['responseCode'];
            $collection_batch->remarks = $response['data']['response'][0]['responseDescription'];
        }
        $collection_batch->save();

        return 200;
    }
}
