<?php

/*
|--------------------------------------------------------------------------
| SEO and social sharing
|--------------------------------------------------------------------------
|
| One place for everything the <x-seo> component, the sitemap and the
| structured data need. Nothing here is secret, so it is committed rather
| than pushed into .env — except the canonical URL, which follows APP_URL so
| local, staging and production each describe themselves honestly.
|
*/

return [

    // The brand as people should see it written.
    'name'  => 'CelebrateMi',

    // How people actually type it. Search engines use these to connect the
    // spellings to this site (schema.org alternateName); the FAQ on the home
    // page answers the same question for humans.
    'aliases' => [
        'CelebrateMe',
        'Celebrate Me',
        'Celebrate Mi',
        'Celebratemi',
        'Celebrate Mi app',
    ],

    'tagline' => 'Celebrate them. Keep it for good.',

    'description' => 'Create your celebration page in 30 seconds. Collect wishes, voice notes, photos and cash gifts from everyone who loves you — on one link that never disappears.',

    /*
    | Canonical origin. Every canonical URL, og:url and sitemap entry is built
    | from APP_URL, so setting it wrong is the fastest way to de-index a site.
    | Production must be https://celebratemi.com — no trailing slash, no www.
    */
    'url' => env('APP_URL', 'https://celebratemi.com'),

    // 1200×630 is what Facebook, LinkedIn, WhatsApp and X all crop from.
    'image' => [
        'path'   => '/og-image.png',
        'width'  => 1200,
        'height' => 630,
        'alt'    => 'CelebrateMi — one page for wishes, photos and gifts, kept for good.',
    ],

    // Fill these in as the accounts are created; empty ones are simply not
    // emitted rather than pointing at a profile that does not exist.
    'social' => [
        'twitter'   => '',   // @handle, no URL
        'instagram' => '',   // full profile URL
        'facebook'  => '',
        'tiktok'    => '',
        'linkedin'  => '',
    ],

    // Search Console / Bing verification tokens, when you have them.
    'verification' => [
        'google' => env('SEO_GOOGLE_VERIFICATION', ''),
        'bing'   => env('SEO_BING_VERIFICATION', ''),
    ],

    // Where the business is, for the Organization record.
    'locale'  => 'en_NG',
    'country' => 'NG',

    /*
    | Details the terms and privacy pages state as fact. Fill every one of
    | these in before launch — they are what a customer, a bank or a regulator
    | reads to know who they are dealing with, and a placeholder in public is
    | worse than no page at all.
    */
    'legal' => [
        'entity'    => env('LEGAL_ENTITY', 'CelebrateMi'),
        'rc_number' => env('LEGAL_RC_NUMBER', ''),
        'address'   => env('LEGAL_ADDRESS', 'Lagos, Nigeria'),
        'email'     => env('LEGAL_EMAIL', 'hello@celebratemi.com'),
        'privacy_email' => env('LEGAL_PRIVACY_EMAIL', 'privacy@celebratemi.com'),
        'jurisdiction'  => env('LEGAL_JURISDICTION', 'the Federal Republic of Nigeria'),
        'effective'     => env('LEGAL_EFFECTIVE', 'September 2026'),
        // Percentage taken from cash gifts. Shown on the pricing page too —
        // keep the two in step.
        'gift_fee_percent' => env('LEGAL_GIFT_FEE', '5'),
    ],
];
