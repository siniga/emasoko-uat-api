<?php

namespace App\Listeners;

use App\Events\CorporateStatus;
use App\Classes\Authentication;
use App\Models\Partner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchCorporateStatus
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
     * @param  \App\Events\CorporateStatus  $event
     * @return void
     */
    public function handle(CorporateStatus $event)
    {
        //payload
        $payload = $event->payload;

        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //API URL 
        $API_URL = env('API_URL');

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update the corporate
        $partner = Partner::where('reference', '=', $payload['data']['corporateReference'])->first();
        $partner->http_response_status_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== CorporateStatus Response  ==');
        Log::debug($response);

        if ($response['status'] == "Success") {
            $partner->status = 202;
            $partner->http_response_code = $response['data']['response'][0]['responseCode'];
            $partner->remarks = $response['data']['response'][0]['responseDescription'];
            $partner->save();

            //TODO 1: Send default password to partner_users

        } else if ($response['status'] == "Fail") {
            $partner->status = 500;
            $partner->http_response_code = $response['data']['response'][0]['responseCode'];
            $partner->remarks = $response['data']['response'][0]['responseDescription'];
            $partner->save();
        }

        return true;
    }
}
