<?php

namespace App\Jobs;

use App\Classes\Authentication;
use App\Models\DebitOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MandateRegistrationJob implements ShouldQueue
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
        Log::debug("Token : {$AUTH_BEARER_TOKEN}");

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN];

        //Create Payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "MandateRegistration",
            "status" => "Create",
            "data" => $this->data
        ];

        //Create Payload
        Log::debug('== MandateRegistration Request == ');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');

        //response
        $response = Http::withOptions(['verify' => false])->withHeaders($headers)->post($API_URL, $payload);

        //update debit response
        $debit_order = DebitOrder::where('reference', '=', $payload['data']['mandateReferenceNumber'])->first();
        $debit_order->http_response_json = $response;

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== MandateRegistration Response ==');
        Log::debug($response);

        // Separate status code
        if ($response['status'] == "Success")
            $debit_order->status = 201;
        else if ($response['status'] == "Fail")
            $debit_order->status == 403;

        //TODO: Send OTP message on success
                

        $debit_order->http_response_code = $response['data']['response'][0]['responseCode'];
        $debit_order->remarks = $response['data']['response'][0]['responseDescription'];
        $debit_order->save();
    }
}
