<?php

namespace App\Jobs;

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
        //$this->auth = new Authentication();
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
        $AUTH_BEARER_TOKEN = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE3MjMwMzU5MTYsIm5iZiI6MTcyMzAzNTkxNiwiZXhwIjoxNzIzMDM2MjE2LCJpc3MiOiJFTWFrYXRvIiwiYXVkIjoiRU1ha2F0byJ9.v6i8-zt7q6H-D5n5lB1uCL589m6sD_-MXjbpSJgdgPg";

        Log::debug("== New Token ==");
        Log::debug($AUTH_BEARER_TOKEN);

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
        Log::debug('CorporateRegistration Request =>' . json_encode($payload));

        //API URL 
        $API_URL = env('API_URL');

        //response
       $response = Http::withHeaders($headers)->post($API_URL, $payload);

        //logging response
       Log::debug('CorporateRegistration Response =>' . $response);
    }
}
