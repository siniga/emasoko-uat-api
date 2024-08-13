<?php

namespace App\Listeners;

use App\Classes\Authentication;
use App\Events\CreateOtp;
use App\Models\OtpRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendCreatedOtp
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
     * @param  \App\Events\CreateOtp  $event
     * @return void
     */
    public function handle(CreateOtp $event)
    {
        //payload
        $payload = $event->payload;

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
                'account_number' => $payload['data']['accountNumber'],
                'action' => 'create',
            ]
        )->first();
        $otp_request->http_response_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== OtpCreate Response  ==');
        Log::debug($response);

        if ($response['status'] == "Success" || $response['status'] == "Fail") {
            $otp_request->http_response_code = $response['data']['response'][0]['responseCode'];
        }
        $otp_request->save();
    }
}
