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
            '## Overview',
            'This documentation covers the External App APIs used to authenticate, manage payment mandates, and view collections.',
            '',
            '## API Groups',
            '- **Auth**: obtain an access token using `POST /v1/login` and `POST /v1/token`.',
            '- **PaymentMandate**: create and manage mandates, OTP, status checks, and cancellation.',
            '- **PaymentCollection**: view all collections, filter by mandate reference, and retrieve actual collections.',
            '',
            '## Authentication Flow',
            '1. Call `POST /v1/login` (or `POST /v1/token`) to get an API token.',
            '2. Send the token in authorized requests to mandate and collection endpoints.',
            '',
            '## Response Format',
            'Most endpoints return `success`, `response_code`, and a payload object/array depending on the operation.',
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
    'servers' => null,

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
