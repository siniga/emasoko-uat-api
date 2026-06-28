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

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'verify_url' => env('RECAPTCHA_VERIFY_URL', 'https://www.google.com/recaptcha/api/siteverify'),
    ],

    'company_registration' => [
        'reviewer_roles' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('COMPANY_REGISTRATION_REVIEWER_ROLES', 'Manager'))
        ))),
        'sms_sender' => env('COMPANY_REGISTRATION_SMS_SENDER', 'EMAKATO'),
    ],

    'daily_report' => [
        'recipient_ids' => array_values(array_filter(array_map(
            'intval',
            explode(',', env('DAILY_REPORT_RECIPIENT_IDS', '37,38'))
        ))),
    ],

    'monthly_report' => [
        'partner_ids' => array_values(array_filter(array_map(
            'intval',
            explode(',', env('MONTHLY_REPORT_PARTNER_IDS', '6'))
        ))),
    ],

];
