<?php

namespace App\Jobs;

use App\Classes\Authentication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
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
        //$AUTH_BEARER_TOKEN = $this->auth->generateToken();
        $AUTH_BEARER_TOKEN = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE3MjMwNTQ0ODEsIm5iZiI6MTcyMzA1NDQ4MSwiZXhwIjoxNzIzMDU0NzgxLCJpc3MiOiJFTWFrYXRvIiwiYXVkIjoiRU1ha2F0byJ9.zZfyp76f-ChCw3ux5roLIxtnahfR5DjqLVALZeyQM0I";

        //Create Headers
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$AUTH_BEARER_TOKEN];

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

        //response
       $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //logging response
       Log::debug('CorporateRegistration Response =>' . $response);
    }
}
