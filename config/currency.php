<?php

return [
    'base' => 'USD',

    /*
    |--------------------------------------------------------------------------
    | Country → Currency Mapping
    |--------------------------------------------------------------------------
    | Keyed by lowercase country name or ISO 3166-1 alpha-2 code.
    */
    'country_map' => [
        'nigeria' => 'NGN',
        'ng'      => 'NGN',
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Meta
    |--------------------------------------------------------------------------
    */
    'currencies' => [
        'USD' => ['symbol' => '$',  'name' => 'US Dollar',       'decimals' => 2],
        'NGN' => ['symbol' => '₦',  'name' => 'Nigerian Naira',  'decimals' => 2],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exchange Rate Source
    |--------------------------------------------------------------------------
    | Set CURRENCY_API_KEY in .env to use open.er-api.com authenticated tier.
    | Leave blank to use the free unauthenticated endpoint.
    */
    'api_key'       => env('CURRENCY_API_KEY', ''),
    'cache_ttl'     => env('CURRENCY_CACHE_TTL', 360), // minutes

    /*
    |--------------------------------------------------------------------------
    | Fallback Rates (USD → X)
    |--------------------------------------------------------------------------
    | Used when the live API is unavailable.
    */
    'fallback_rates' => [
        'NGN' => 1620.00,
    ],
];
