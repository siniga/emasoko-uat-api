<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     * Your API path. By default, all routes starting with this path will be added to the docs.
     * If you need to change this behavior, you can add your custom routes resolver using `Scramble::routes()`.
     */
    'api_path' => 'api',

    /*
     * Your API domain. By default, app domain is used. This is also a part of the default API routes
     * matcher, so when implementing your own, make sure you use this config if needed.
     */
    'api_domain' => null,

    /*
     * Define the theme of the documentation.
     * Available options are `light` and `dark`.
     */
    'theme' => 'light',

    'info' => [
        /*
         * API version.
         */
        'version' => env('API_VERSION', '0.0.1'),

        /*
         * Description rendered on the home page of the API documentation (`/docs/api`).
         */
        'description' => implode("\n", [
            '## eMakato External App API',
            '',
            'The External App API lets approved service providers create payment mandates, verify mandate OTPs, check mandate status, retrieve expected and actual collections, and receive asynchronous callbacks when mandate or collection statuses change.',
            '',
            'All endpoint paths shown in this documentation are relative to the configured API server URL.',
            '',
            '## Authentication',
            '',
            'Use `POST /v1/token` to obtain an API token. Include the returned token in subsequent requests using the `Authorization` header:',
            '',
            '```http',
            'Authorization: Bearer <access_token>',
            'Accept: application/json',
            'Content-Type: application/json',
            '```',
            '',
            'The token identifies the authenticated user and the partner or partners linked to that user. If a user is linked to more than one partner, requests that operate within a partner context may require `corporate_reference`.',
            '',
            '## API Groups',
            '',
            '- **Auth**: token generation for External App integrations.',
            '- **Payment Mandates**: mandate summaries, listing, lookup, payment channels, creation, OTP, status refresh, and cancellation.',
            '- **Payment Collections**: expected collections, filtering, and actual collected amounts.',
            '',
            '## Recommended Mandate Flow',
            '',
            '1. Call `POST /v1/token` to get an API token.',
            '2. Call `GET /v1/mandate/channels` to fetch active payment channels.',
            '3. Create the mandate using `POST /v1/mandate/create` and send `payment_channel`; do not send `channel`.',
            '4. For NMB mandates, create or verify OTP where required, then use `POST /v1/mandate/status` to refresh status when needed.',
            '5. Listen for mandate callbacks so your system receives final status changes without polling eMakato continuously.',
            '',
            '## Payment Channel Rules',
            '',
            '- `payment_channel` is the provider slug returned by `GET /v1/mandate/channels`, for example `NMB` or `CRDB`.',
            '- `channel` is derived internally and should not be sent in mandate creation requests.',
            '- NMB mandates require bank-side verification and may move through OTP or pending status before becoming active.',
            '- CRDB mandates may become active through CRDB request-status callbacks.',
            '',
            '## Partner Context',
            '',
            'If the authenticated user is linked to one partner, eMakato can resolve the partner automatically. If the user is linked to more than one partner, send `corporate_reference` so the API knows which partner owns the mandate or collection request.',
            '',
            '## Response Format',
            '',
            'Most endpoints return:',
            '',
            '```json',
            '{',
            '  "success": true,',
            '  "response_code": 200,',
            '  "message": "Request processed successfully"',
            '}',
            '```',
            '',
            'Some endpoints include additional objects such as `mandate`, `mandates`, `collections`, `summaries`, or `channels` depending on the operation.',
            '',
            '## Status Fields',
            '',
            '- `lifecycle_status` describes the mandate lifecycle, for example `draft`, `submitted`, `awaiting_otp`, `verified`, `active`, `failed`, `cancellation_requested`, `cancelled`, or `closed`.',
            '- Collection `status` describes the installment result, for example `PENDING`, `PAID`, `PARTIAL_PAID`, `FUNDSCHECK`, or `FAILED`.',
            '- `FUNDSCHECK` means the bank reported insufficient funds. It is kept as its own status so providers can retry or follow up separately from hard failures.',
            '',
            '## Provider Callbacks',
            '',
            'If your partner profile has a `callback_url`, eMakato sends asynchronous callbacks for mandate and collection status changes. Callback payloads use `callback_type` to identify the payload category:',
            '',
            '- `MANDATE_STATUS`',
            '- `COLLECTION_STATUS`',
            '',
            'Common callback envelope fields:',
            '',
            '```json',
            '{',
            '  "event_id": "8f3c88f2-a8cf-4c01-8f7e-9085e5a1bb02",',
            '  "callback_type": "MANDATE_STATUS",',
            '  "event_type": "MANDATE_ACTIVE",',
            '  "event_time": "2026-06-11T10:15:30+03:00",',
            '  "corporate_reference": "CO0001",',
            '  "channel": "NMB"',
            '}',
            '```',
            '',
            'Mandate callback event examples:',
            '',
            '- `MANDATE_ACTIVE`',
            '- `MANDATE_FAILED`',
            '- `MANDATE_CANCELLED`',
            '- `MANDATE_CANCELLATION_REQUESTED`',
            '- `MANDATE_SUBMITTED`',
            '- `MANDATE_VERIFIED`',
            '',
            'Collection callback event examples:',
            '',
            '- `COLLECTION_PAID`',
            '- `COLLECTION_PARTIAL_PAID`',
            '- `COLLECTION_FUNDSCHECK`',
            '- `COLLECTION_FAILED`',
            '',
            '### Callback Signature',
            '',
            'If `callback_secret` is configured for your partner, callbacks include an HMAC signature header:',
            '',
            '```http',
            'X-Signature: sha256=<hex_hmac_sha256>',
            'X-Signature-Algorithm: HMAC-SHA256',
            '```',
            '',
            'Verify the signature using the exact raw request body:',
            '',
            '```php',
            '$expected = \'sha256=\' . hash_hmac(\'sha256\', $rawRequestBody, $callbackSecret);',
            '',
            'if (!hash_equals($expected, $requestSignature)) {',
            '    // reject request',
            '}',
            '```',
            '',
            '### Callback Retries',
            '',
            'eMakato retries callbacks for temporary failures: connection errors, timeouts, HTTP `408`, HTTP `429`, and HTTP `5xx`. The retry schedule is 1 minute, 5 minutes, 15 minutes, and 30 minutes, with up to 5 attempts within 24 hours. HTTP `4xx` responses other than `408` and `429` are treated as non-retryable.',
            '',
            '## Sample Mandate Callback',
            '',
            '```json',
            '{',
            '  "event_id": "8f3c88f2-a8cf-4c01-8f7e-9085e5a1bb02",',
            '  "callback_type": "MANDATE_STATUS",',
            '  "event_type": "MANDATE_ACTIVE",',
            '  "event_time": "2026-06-11T10:15:30+03:00",',
            '  "corporate_reference": "CO0001",',
            '  "channel": "NMB",',
            '  "mandate": {',
            '    "payment_channel": "NMB",',
            '    "reference": "2801202624643",',
            '    "periodicity": "MONTHLY",',
            '    "debit_type": "FIXED",',
            '    "installment_amount": 100000,',
            '    "min_installment_amount": 100000,',
            '    "max_installment_amount": 100000,',
            '    "total_amount": 1200000,',
            '    "paid_amount": 0,',
            '    "outstanding_amount": 1200000,',
            '    "number_of_installment": 12,',
            '    "start_date": "2026-06-01",',
            '    "end_date": "2027-05-01",',
            '    "lifecycle_status": "active",',
            '    "remarks": "Mandate approved"',
            '  }',
            '}',
            '```',
            '',
            '## Sample Collection Callback',
            '',
            '```json',
            '{',
            '  "event_id": "dc69f0c9-5381-48af-a0cd-99bdc9dfdf55",',
            '  "callback_type": "COLLECTION_STATUS",',
            '  "event_type": "COLLECTION_FUNDSCHECK",',
            '  "event_time": "2026-06-11T10:20:45+03:00",',
            '  "corporate_reference": "CO0001",',
            '  "channel": "NMB",',
            '  "collection": {',
            '    "mandate": {',
            '      "reference": "2801202624643",',
            '      "debit_type": "FIXED",',
            '      "start_date": "2026-06-01",',
            '      "end_date": "2027-05-01",',
            '      "lifecycle_status": "active"',
            '    },',
            '    "installment_amount": 100000,',
            '    "current_balance": 0,',
            '    "outstanding_amount": 100000,',
            '    "last_paid_amount": 0,',
            '    "payment_date": "2026-06-06",',
            '    "effective_payment_date": "2026-06-06",',
            '    "reference": "COL-202606060001-897426",',
            '    "status": "FUNDSCHECK",',
            '    "remarks": "Insufficient funds"',
            '  },',
            '  "meta": {',
            '    "settlement_reference": "COL-202606060001-897426",',
            '    "external_reference": null,',
            '    "bank_status": "FUNDSCHECK",',
            '    "settlement_status": "FUNDSCHECK",',
            '    "paid_amount": 0,',
            '    "transaction_at": "2026-06-06T08:08:36+03:00"',
            '  }',
            '}',
            '```',
        ]),
    ],

    /*
     * Customize Stoplight Elements UI
     */
    'ui' => [
        /*
         * Hide the `Try It` feature. Enabled by default.
         */
        'hide_try_it' => false,

        /*
         * URL to an image that displays as a small square logo next to the title, above the table of contents.
         */
        'logo' => '',

        /*
         * Use to fetch the credential policy for the Try It feature. Options are: omit, include (default), and same-origin
         */
        'try_it_credentials_policy' => 'include',
    ],

    /*
     * The list of servers of the API. By default, when `null`, server URL will be created from
     * `scramble.api_path` and `scramble.api_domain` config variables. When providing an array, you
     * will need to specify the local server URL manually (if needed).
     *
     * Example of non-default config (final URLs are generated using Laravel `url` helper):
     *
     * ```php
     * 'servers' => [
     *     'Live' => 'api',
     *     'Prod' => 'https://scramble.dedoc.co/api',
     * ],
     * ```
     */
    'servers' => [
        'Production' => env('SCRAMBLE_SERVER_URL', 'https://uat.emakato.co.tz/api'),
    ],

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],

    'extensions' => [],

    //additional Scramble configuration
    'route_groups' => [
        [
            'match' => [
                'prefix' => 'public', // ✅ only show this prefix in public docs
            ],
            'name' => 'Public APIs',
            'include_in_documentation' => true,
        ],
        [
            'match' => [
                'prefix' => 'admin',
            ],
            'name' => 'Admin APIs',
            'include_in_documentation' => false, // 🔒 hide private APIs from public docs
        ],
    ],
];
