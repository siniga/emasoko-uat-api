<?php

namespace App\Classes;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CrdbSession
{
    private $password;
    private $partnerId;
    private string $sessionUrl;

    public function __construct()
    {
        $this->password = config('services.crdb.session.password');
        $this->partnerId = config('services.crdb.session.partner_id');
        $this->sessionUrl = config('services.crdb.session.url');
    }

    public function getToken(): string
    {
        return Cache::remember('crdb.sessionToken', now()->addHours(23), function () {
            $payload = [
                'password'  => config('crdb.password'),
                'partnerID' => config('crdb.partner_id'),
                'code'      => 'SESSION',
            ];

            //headers
            $headers = [
                'Accept' => '*/*',
                'Content-Type' => 'application/json',
            ];

            // Send the POST request using Laravel's HTTP client
            $response = Http::withHeaders($headers)->post(
                $this->sessionUrl,
                [
                    'password'  => $this->password,
                    'partnerID' => $this->partnerId,
                    'code'      => 'SESSION',
                ]
            );

            //json object
            $response = $response->json();
            Log::debug("== Session Token ==");
            Log::debug($response);

            $token = data_get($response->json(), 'data.sessionToken');
            if (!$token) {
                throw new RuntimeException('CRDB SESSION missing sessionToken');
            }

            return $token;
        });
    }

    public function forgetToken(): void
    {
        Cache::forget('crdb.sessionToken');
    }
}
