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

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //create authentication token
        $AUTH_BEARER_TOKEN = "";

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
       Log::channel('apiLogs')->Info('CorporateRegistration Response =>' . $response);
    }
}
