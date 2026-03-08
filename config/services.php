<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'jumena' => [
        'token_url' => env('JUMENA_TOKEN_URL'),
        'payment_session_url' => env('JUMENA_PAYMENT_SESSION_URL'),
        'client_id' => env('YAS_CLIENT_ID'),
        'client_secret' => env('YAS_CLIENT_SECRET'),
    ],

    'callbacks' => [
        'enforce_signatures' => env('CALLBACK_SIGNATURE_ENFORCED', true),
        'providers' => [
            'crdb' => [
                'header' => env('CRDB_CALLBACK_SIGNATURE_HEADER', 'X-Signature'),
                'secret' => env('CRDB_CALLBACK_SECRET'),
            ],
            'airtel' => [
                'header' => env('AIRTEL_CALLBACK_SIGNATURE_HEADER', 'X-Signature'),
                'secret' => env('AIRTEL_CALLBACK_SECRET'),
            ],
            'yas' => [
                'header' => env('YAS_CALLBACK_SIGNATURE_HEADER', 'X-Signature'),
                'secret' => env('YAS_CALLBACK_SECRET'),
            ],
        ],
    ],

];
