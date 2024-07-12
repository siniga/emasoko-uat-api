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
        // $response = Http::post('API_TOKEN_URL', [
        //     'username' => '',
        //     'password' => ''
        // ]);

        //json object
        // $response = $response->json();
        // Log::debug($response);


        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE3MjA3NzU5NjcsIm5iZiI6MTcyMDc3NTk2NywiZXhwIjoxNzIwNzc2MjY3LCJpc3MiOiJFTWFrYXRvIiwiYXVkIjoiRU1ha2F0byJ9.gbjndpiNHqXAYatCwltVwlefaZXlgvmzAvMNZjq_NJM';

        return $token;
    }
}
