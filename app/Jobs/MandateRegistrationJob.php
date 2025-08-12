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
        Log::debug('== MandateRegistration Request Sent== ');
        Log::debug($payload);

        //API URL 
        $API_URL = env('API_URL');
        Log::debug("API URL =>" . $API_URL);

        //response
        $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //update debit response
        $debit_order = DebitOrder::where('reference', '=', $payload['data']['mandateReferenceNumber'])->first();
        Log::debug("== Debit Mandate ==");
        Log::debug($debit_order);

        //decode the response 
        $response = json_decode($response, true);

        //logging response
        Log::debug('== MandateRegistration Response ==');
        Log::debug($response);

        if($response) {
            // Separate status code
            if (strtolower($response['status']) == "success"){
                $debit_order->status = 201;
                $debit_order->otp_reference = $response['data']['otpCaseReference'];
            } else if(strtolower($response['status']) == "fail"){
                if ($response['data']['response'][0]['responseCode'] != 410)
                    $debit_order->status = 403;
            }       

            $debit_order->http_response_code = $response['data']['response'][0]['responseCode'];
            $debit_order->remarks = $response['data']['response'][0]['responseDescription'];
            $debit_order->save();

            //TODO: Send OTP message on FAILURE
            if(strtolower($response['status']) == "fail"){

            }
        } else {
            Log::debug("No response from NMB end");
        }
    }
}
