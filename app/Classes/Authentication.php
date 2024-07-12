<?php

namespace App\Classes;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Authentication
{
    public function __construct()
    {
    }

    function create_token()
    {
        $response = Http::post('API_TOKEN_URL', [
            'username' => '',
            'password' => ''
        ]);

        //json object
        $response = $response->json();
        Log::debug($response);

        return $response['data']['token'];
    }
}
