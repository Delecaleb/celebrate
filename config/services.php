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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paystack' => [
        'secret'     => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    ],

    'stripe' => [
        'secret' => env("STRIPE_SECRET_KEY"),
        'publishable_key' => env("STRIPE_PUBLISHABLE_KEY"),
        // Signing secret for the endpoint, from the Stripe dashboard. This is
        // not the API key — a webhook signed with the wrong one is rejected.
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'ipinfo' => [
        'token' => env('IPINFO_TOKEN'),

        /*
        | Development only. A request from 127.0.0.1 cannot be geolocated, so
        | local signups never exercise the real lookup. Set this to any public
        | address and detection runs against that instead — the fastest way to
        | prove the Nigeria path works before deploying. Leave empty in
        | production, where it is ignored anyway.
        */
        'dev_ip' => env('LOCATION_DEV_IP', ''),
    ],
];
