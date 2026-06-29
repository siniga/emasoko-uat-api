<?php

namespace App\Classes;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AirtelToken
{
    private $client_id;
    private $client_secret;

    public function __construct()
    {
        $this->client_id = config('services.airtel.client_id');
        $this->client_secret = config('services.airtel.client_secret');
    }

    public function generate()
    {
        //headers
        $headers = [
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
        ];

        //API URL
        $API_URL = config('services.airtel.token_url');

        // Send the POST request using Laravel's HTTP client
        $response = Http::withHeaders($headers)->post(
            $API_URL,
            [
                "client_id" => $this->client_id,
                "client_secret" => $this->client_secret,
                "grant_type" => "client_credentials"
            ]
        );

        //json object
        $response = $response->json();
        Log::debug($response);

        return $response['access_token'];
    }
}
