<?php

namespace App\Classes;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Authentication
{
    private $username;
    private $passPhrase;

    public function __construct()
    {
        $this->username = env("NMB_USERNAME");
        $this->passPhrase = env("NMB_PASSPHRASE");
    }

    public function generateToken()
    {
        // $header = [
        //     'alg' => 'HS256',
        //     'typ' => 'JWT'
        // ];

        // $issuedAt = time();
        // $expiresAt = $issuedAt + 300;

        // //create payload
        // $payload = [
        //     'iat' =>  $issuedAt,
        //     'nbf' =>  $issuedAt,
        //     'exp' => $expiresAt,
        //     'iss' => $this->username,
        //     'aud' =>  $this->username,
        // ];

        // $b64Header = $this->toBase64(json_encode($header));
        // $b64Payload =  $this->toBase64(json_encode($payload));

        // //prep signature
        // $preSignature = $b64Header . '.' . $b64Payload;
        // $signature = $this->generateSignature($this->passPhrase, $preSignature);

        // $jwtToken = $b64Header . '.' . $b64Payload . '.' . $signature;
        $jwtToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE3MjMxOTQyMzEsIm5iZiI6MTcyMzE5NDIzMSwiZXhwIjoxNzIzMTk0NTMxLCJpc3MiOiJFTWFrYXRvIiwiYXVkIjoiRU1ha2F0byJ9.tSDqC8WE2R3RfJ8kXkgbs_eNJy4kTaBb1mRY-K6_R_w";
        return $jwtToken;
    }

    //to Base64
    function toBase64($source)
    {
        // Encode in classical base64
        $encoded_source = base64_encode($source);

        // Remove padding equal characters
        $encoded_source =  preg_replace('/=+$/', '', $encoded_source);

        // Replace characters according to base64url specifications
        $encoded_source = str_replace('+', '-', $encoded_source);
        $encoded_source = str_replace('/', '-', $encoded_source);

        return $encoded_source;
    }

    //base64 url
    function u8Base64url($source)
    {
        $encoded_source = $this->base64_encode_source($source);

        // Remove padding equal characters
        $encoded_source =  preg_replace('/=+$/', '', $encoded_source);

        // Replace characters according to base64url specifications
        $encoded_source = str_replace('+', '-', $encoded_source);
        $encoded_source = str_replace('/', '-', $encoded_source);

        return $encoded_source;
    }

    //generate signature
    function generateSignature($key, $message)
    {
        $base64_signature = '';

        try {
            $signature = hash_hmac('sha256', $message, $key, true);

            //base64 signature
            $base64_signature = $this->u8Base64url($signature);
        } catch (Exception $e) {
            Log::debug("Message: " . $e->getMessage());
        }
        return $base64_signature;
    }

    // Assuming $source is a binary string
    function base64_encode_source($source)
    {
        // Convert the binary string into an array of ASCII values
        $characters = array_map('ord', str_split($source));

        // Convert ASCII values to characters and combine them into a string
        $char_string = implode(array_map('chr', $characters));

        // Encode the string in Base64
        $encoded_source = base64_encode($char_string);

        return $encoded_source;
    }
}
