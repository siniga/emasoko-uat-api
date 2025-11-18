<?php

namespace App\Listeners;

use App\Classes\Authentication;
use App\Events\MandateStatus;
use App\Models\DebitOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchMandateStatus
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
     * @param  \App\Events\MandateStatus  $event
     * @return void
     */
    public function handle(MandateStatus $event)
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

        //update the debit order
        $debit_order = DebitOrder::where('reference', '=', $payload['data']['mandateReferenceNumber'])->first();
        $debit_order->http_response_status_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== MandateStatus Response  ==');
        Log::debug($response);

        if (strtolower($response['status']) == "success") {
            $debit_order->http_response_code = $response['data']['response'][0]['responseCode'];
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];
        } else if(strtolower($response['status']) == "fail") {
            $debit_order->status = 500;
            $debit_order->http_response_code = $response['data']['response'][0]['responseCode'];
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];
        }
        $debit_order->save();
    }
}
