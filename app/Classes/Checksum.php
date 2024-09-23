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
            'Authorization' => 'Basic Q2JpZTFLOVdVd1c0Y0RFTFlPNnB2ZjJGQU9ZYTpaS0ZxMmxkbHQ1ZlV3aURDc2t0QUFiSm9wQmNh',
            'Cookie' => 'BIGipServerCRDB_DC_ESB_STB_WSO2_POOL_443=363664044.47873.0000; route=1726646754.062.70.52380'
          ];

          //options
          $options = [
          'form_params' => [
            'grant_type' => 'client_credentials',
            'scope' => 'common'
          ]];

        //API URL
        $API_URL = 'https://pre-prod-esb-wso2is.crdbbank.co.tz/oauth2/token';

        //trigger authentication api
        $response = Http::withHeaders($headers)->post($API_URL, $options);
        //$response = Http::post($API_URL, $options, $headers);

        //json object
        $response = $response->json();
        Log::debug($response);

        return $response['access_token'];
    }


    /**
     * Generate a checksum using SHA-256 algorithm
     * 
     * @param string $data
     * @param string $salt
     * @return string
     * @throws Exception
     */
    public function generateChecksum(string $data, string $salt): string
    {
        // Concatenate the data and salt
        $dataWithSalt = $data . $salt;

        // Generate the hash using SHA-256
        $hash = hash('sha256', $dataWithSalt);

        // Return the hex-encoded checksum
        return $hash;
    }
}