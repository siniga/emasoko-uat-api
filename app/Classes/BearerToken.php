<?php

namespace App\Classes;

use App\Models\DebitOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BearerToken
{
    private ?DebitOrder $debitOrder = null;
    private ?string $clientId = null;
    private ?string $clientSecret = null;
    private ?string $tokenUrl = null;

    public function __construct($debitOrder = null, ?string $clientId = null, ?string $clientSecret = null, ?string $tokenUrl = null)
    {
        if ($debitOrder instanceof DebitOrder) {
            $this->debitOrder = $debitOrder;
        }

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tokenUrl = $tokenUrl ?: config('services.jumena.payment_token_url');
    }

    public function generate()
    {
        //headers
        $headers = [
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
        ];

        //set clientId and clientSecret from debit order periodicity only when not pre-configured
        if (!$this->clientId || !$this->clientSecret) {
            if (!$this->debitOrder) {
                throw new \RuntimeException('BearerToken requires either explicit client credentials or a DebitOrder.');
            }

            if ($this->debitOrder->periodicity === "DAILY") {
                $this->clientId = config('services.jumena.periodic_credentials.daily.client_id');
                $this->clientSecret = config('services.jumena.periodic_credentials.daily.client_secret');
            } else if ($this->debitOrder->periodicity === "WEEKLY") {
                $this->clientId = config('services.jumena.periodic_credentials.weekly.client_id');
                $this->clientSecret = config('services.jumena.periodic_credentials.weekly.client_secret');
            } else if ($this->debitOrder->periodicity === "MONTHLY") {
                $this->clientId = config('services.jumena.periodic_credentials.monthly.client_id');
                $this->clientSecret = config('services.jumena.periodic_credentials.monthly.client_secret');
            }
        }

        if (!$this->clientId || !$this->clientSecret) {
            throw new \RuntimeException('BearerToken client credentials are missing.');
        }

        if (!$this->tokenUrl) {
            throw new \RuntimeException('BearerToken token URL is missing.');
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
