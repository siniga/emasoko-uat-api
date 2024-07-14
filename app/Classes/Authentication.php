<?php

namespace App\Classes;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Authentication
{
    private $key;

    public function __construct()
    {
        $this->key = "SuperSecretKey12345678901234567890";
    }

    public function generateToken(int $exp = 3600): string
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $issuedAt = time();
        $expiresAt = $issuedAt + $exp;

        //create payload
        $payload = [
            'iat' =>  $issuedAt,
            'nbf' =>  $issuedAt,
            'exp' => $expiresAt,
            'iss' => 'EMakato',
            'aud' =>  'EMakato'
        ];

        $payload = base64_encode(json_encode($payload));

        $signature = hash_hmac('sha256', "$header.$payload", $this->key, true);
        $signature = base64_encode($signature);

        return "$header.$payload.$signature";
    }

    public function validateToken(string $token): bool
    {
        $token_parts = explode('.', $token);
        if (count($token_parts) !== 3) {
            return false;
        }

        $header = base64_decode($token_parts[0]);
        $payload = base64_decode($token_parts[1]);
        $signature = base64_decode($token_parts[2]);

        $header_data = json_decode($header);
        if (!isset($header_data->alg) || $header_data->alg !== 'HS256') {
            return false;
        }

        $valid_signature = hash_hmac('sha256', "$header.$payload", $this->key, true);
        $valid_signature = base64_encode($valid_signature);

        return ($signature === $valid_signature);
    }

    public function decodeToken(string $token): object
    {
        $payload = base64_decode(explode('.', $token)[1]);
        return json_decode($payload);
    }
}
