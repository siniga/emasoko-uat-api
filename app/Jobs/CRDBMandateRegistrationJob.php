<?php

namespace App\Jobs;

use App\Classes\Checksum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CRDBMandateRegistrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data;
    private $auth;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->auth = new Checksum();
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
        $headers = [
            'Channel' => 'IB',
            'AppId' => 'INTERNET',
            'AppVersion' => '1.0.0',
            "RequestId" => $this->data['requestId'],
            'Authorization' => 'Bearer ' . $AUTH_BEARER_TOKEN,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        //API URL
        $API_URL = env('CRDB_API_URL');

        //push payload => NMB Direct Debits
        $response = Http::withHeaders($headers)->post($API_URL, $this->data['payload']);

        Log::debug("== Response ==");
        Log::debug($response->status());
        Log::debug($response);

        //send response to eMakato
        return response()->json($response->json());
    }
}
