<?php

namespace App\Classes;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BearerToken
{
    private $clientId;
    private $clientSecret;
    private $baseUrl;

    public function __construct($clientId, $clientSecret, $baseUrl)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->baseUrl = $baseUrl;
    }

    public function generate()
    {
        //headers
        $headers = [
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
        ];

        // Send the POST request using Laravel's HTTP client
        $response = Http::withHeaders($headers)->post(
            $this->baseUrl,
            [
                "client_id" => $this->clientId,
                "client_secret" => $this->clientSecret,
                "grant_type" => "client_credentials"
            ]
        );

        //json object
        $response = $response->json();
        Log::debug($response);

        return $response['access_token'];
    }
}
