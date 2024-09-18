<?php

namespace App\Jobs;

use App\Classes\Authentication;
use App\Models\Partner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CorporateRegistrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $data;
    private $auth;

    public function __construct($data)
    {
        $this->data = $data;
        $this->auth = new Authentication();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = $this->auth->generateToken();

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "CorporateRegistration",
            "status" => "Create",
            "data" => $this->data
        ];

        //Create Payload
        Log::debug('== CorporateRegistration Request == ');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');
        Log::debug("API URL => ". $API_URL);

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update the corporate
        $partner = Partner::where('reference', '=', $payload['data']['corporateReference'])->first();
        $partner->http_response_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== CorporateRegistration Response ==');
        Log::debug($response);

        if (strtolower($response['status']) == "success") {
            $partner->status = 201;
        } else if (strtolower($response['status']) == "fail") {
            if ($response['data']['response'][0]['responseCode'] != 410)
                $partner->status = 403;
        }

        $partner->http_response_code = $response['data']['response'][0]['responseCode'];
        $partner->remarks = $response['data']['response'][0]['responseDescription'];
        $partner->save();
    }
}
