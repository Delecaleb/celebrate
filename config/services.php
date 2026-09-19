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

    /*
    | AlatPay (Wema). Naira only, and off until its keys are set — see
    | App\Services\PaymentSystem\AlatPayService.
    */
    'alatpay' => [
        'base_url'         => env('ALATPAY_BASE_URL', 'https://apibox.alatpay.ng'),
        'business_id'      => env('ALATPAY_BUSINESS_ID'),
        // Sent as Ocp-Apim-Subscription-Key on every call.
        'subscription_key' => env('ALATPAY_SUBSCRIPTION_KEY'),
        'webhook_secret'   => env('ALATPAY_WEBHOOK_SECRET'),
        // Handed to the browser to open AlatPay's own checkout. Falls back to
        // the subscription key, which is what AlatPay's own plugins send.
        'public_key'       => env('ALATPAY_PUBLIC_KEY'),
        // AlatPay's hosted checkout — card, transfer and USSD in their UI, so
        // no card detail ever reaches us. Test builds point at the azure host.
        'checkout_js'      => env('ALATPAY_CHECKOUT_JS', 'https://web.alatpay.ng/js/alatpay.js'),
        // On: AlatPay's checkout opens and the payer picks a channel — whichever
        // ones are enabled on the business in their portal. Off: skip it and go
        // straight to a transfer account. Their script offers no way to show
        // some channels and not others, so this is the only choice we have.
        'full_checkout'    => env('ALATPAY_FULL_CHECKOUT', true),
        // Where a transaction is read back from. AlatPay's public docs do not
        // spell this path out, so it is configurable rather than compiled in.
        'status_path'      => env('ALATPAY_STATUS_PATH', '/alatpaytransaction/api/v1/transactions/{id}'),
        'enabled'          => env('ALATPAY_ENABLED', false),
    ],

    'paystack' => [
        'secret'     => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        // Whether new checkouts may start. Overridden from Settings → Payments.
        'enabled'    => env('PAYSTACK_ENABLED', true),
    ],

    'stripe' => [
        'secret' => env("STRIPE_SECRET_KEY"),
        'publishable_key' => env("STRIPE_PUBLISHABLE_KEY"),
        // Signing secret for the endpoint, from the Stripe dashboard. This is
        // not the API key — a webhook signed with the wrong one is rejected.
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        // Whether new checkouts may start. Overridden from Settings → Payments.
        'enabled' => env('STRIPE_ENABLED', true),
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
