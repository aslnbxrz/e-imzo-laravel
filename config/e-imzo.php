<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | E-IMZO Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the E-IMZO service. This should be the full URL
    | including the protocol (http:// or https://).
    |
    */
    'base_url' => env('E_IMZO_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | E-IMZO API Key
    |--------------------------------------------------------------------------
    |
    | The API key for authenticating with the E-IMZO service.
    | This key will be sent in the Authorization header if provided.
    |
    */
    'api_key' => env('E_IMZO_API_KEY'),
];
