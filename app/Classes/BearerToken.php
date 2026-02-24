<?php

namespace App\Classes;

use App\Models\DebitOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BearerToken
{
    private DebitOrder $debitOrder;
    private $clientId;
    private $clientSecret;
    private string $tokenUrl;

    public function __construct($debitOrder)
    {
        $this->debitOrder = $debitOrder;
        $this->tokenUrl = env("JUMENA_PAYMENT_TOKEN_URL");
    }

    public function generate()
    {
        //headers
        $headers = [
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
        ];

        //set clientId and ClientSecret
        if ($this->debitOrder->periodicity === "DAILY") {
            $this->clientId = env("JUMENA_DAILY_CLIENT_ID");
            $this->clientSecret = env("JUMENA_DAILY_CLIENT_SECRET");
        } else if ($this->debitOrder->periodicity === "WEEKLY") {
            $this->clientId = env("JUMENA_WEEKLY_CLIENT_ID");
            $this->clientSecret = env("JUMENA_WEEKLY_CLIENT_SECRET");
        } else if ($this->debitOrder->periodicity === "MONTHLY") {
            $this->clientId = env("JUMENA_MONTHLY_CLIENT_ID");
            $this->clientSecret = env("JUMENA_MONTHLY_CLIENT_SECRET");
        }

        // Send the POST request using Laravel's HTTP client
        $response = Http::withHeaders($headers)->post(
            $this->tokenUrl,
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
