<?php

namespace App\Listeners;

use App\Classes\Authentication;
use App\Events\VerifyOtp;
use App\Models\DebitOrder;
use App\Models\OtpRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpVerificationRequest
{
    private $auth;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        $this->auth = new Authentication();
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(VerifyOtp $event)
    {
        //payload
        $payload = $event->payload;
        $account_number = $event->account_number;
        $mandate_reference = $event->mandate_reference;

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();
        Log::debug("Token : {$AUTH_BEARER_TOKEN}");

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

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
            $otp_request->http_response_code = $response['data']['response'][0]['responseCode'];

            //Update mandate status based on response
            $debit_order = DebitOrder::where('reference', '=', $mandate_reference)->first();
            $debit_order->status = 202;
            $debit_order->approved = 'APPROVED';
            $debit_order->save();
        } else if ($response['status'] == "Fail") {
            $otp_request->http_response_code = $response['data']['response'][0]['responseCode'];

            //Update mandate status based on response
            $debit_order = DebitOrder::where('reference', '=', $mandate_reference)->first();
            $debit_order->status = 500;
            $debit_order->approved = 'REJECTED';
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];
            $debit_order->save();
        }
        $otp_request->save();
    }
}
