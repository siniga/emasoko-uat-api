<?php

namespace App\Classes;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class Checksum
{
    public function validateConfiguration(): void
    {
        foreach (['token_url' => 'CRDB_TOKEN_URL', 'consumer_key' => 'CRDB_CONSUMER_KEY', 'consumer_secret' => 'CRDB_CONSUMER_SECRET'] as $key => $environmentKey) {
            $value = config('services.crdb.' . $key);
            if (!is_string($value) || trim($value) === '') {
                throw ValidationException::withMessages([
                    'crdb' => "CRDB integration is not configured: {$environmentKey} is missing. Contact your administrator.",
                ]);
            }
        }
    }

    public function generateToken()
    {
        $this->validateConfiguration();
        //headers
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . base64_encode(config('services.crdb.consumer_key') . ':' . config('services.crdb.consumer_secret')),
            'Cookie' => 'BIGipServerCRDB_DC_ESB_STB_WSO2_POOL_443=363664044.47873.0000; route=1726646754.062.70.52380'
          ];

        //API URL
        $API_URL = config('services.crdb.token_url');

        // Send the POST request using Laravel's HTTP client
        $response = Http::withHeaders($headers)->asForm()->post($API_URL,
        [
            'grant_type' => 'client_credentials',
            'scope' => 'common'
        ]);

        //json object
        $response->throw();
        $token = $response->json('access_token');
        if (!is_string($token) || trim($token) === '') {
            throw ValidationException::withMessages([
                'crdb' => 'CRDB authentication did not return an access token. Contact your administrator.',
            ]);
        }

        return $token;
    }


    /**
     * Generate a checksum using SHA-256 algorithm
     * 
     */
    public function generateChecksum(string $data): string
    {
        // Concatenate the data and salt
        $dataWithSalt = $data . config('services.crdb.salt');

        // Generate the hash using SHA-256
        $hash = hash('sha256', $dataWithSalt);

        // Return the hex-encoded checksum
        return $hash;
    }
}
