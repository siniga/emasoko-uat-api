<?php

namespace App\Classes;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Checksum
{
    public function generateToken()
    {
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
        $response = $response->json();
        Log::debug($response);

        return $response['access_token'];
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
