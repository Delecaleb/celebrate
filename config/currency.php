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
    | Country assumed when we cannot detect one
    |--------------------------------------------------------------------------
    | A private IP in development, an unreachable lookup, a request with no
    | usable address — detection returns nothing, and something still has to be
    | decided. Falling straight to the base currency made every such signup a
    | USD account, which is wrong for a platform whose customers are mostly in
    | one country.
    |
    | This is that country. It goes through country_map like any other, so
    | changing it — or clearing it to fall through to the base currency — is a
    | one-line edit and no code changes.
    */
    'fallback_country' => env('CURRENCY_FALLBACK_COUNTRY', 'NG'),

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

    /*
    |--------------------------------------------------------------------------
    | Smallest withdrawal, per currency
    |--------------------------------------------------------------------------
    | Set by an admin against each currency (Settings → Currencies) and read
    | back through App\Support\WithdrawalLimits. A currency with no figure, or
    | zero, has no minimum — which is how every currency starts.
    */
    'minimums' => [
        // 'NGN' => 5000.00,
    ],
];
