<?php

namespace App\Services\PaymentSystem;

use Stripe\StripeClient;

class StripeService
{
    private StripeClient $client;

    public function __construct()
    {
        $this->client = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe PaymentIntent and return the client secret.
     */
    public function createPaymentIntent(float $amount, string $currency, array $metadata = []): string
    {
        $intent = $this->client->paymentIntents->create([
            'amount'   => (int) round($amount * 100), // cents
            'currency' => strtolower($currency),
            'metadata' => $metadata,
        ]);

        return $intent->client_secret;
    }

    /**
     * Create a Stripe Checkout Session and return the hosted payment URL.
     */
    public function createCheckoutSession(
        float  $amount,
        string $currency,
        string $successUrl,
        string $cancelUrl,
        array  $metadata = [],
    ): string {
        $session = $this->client->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => strtolower($currency),
                    'product_data' => ['name' => 'Wallet Top-up'],
                    'unit_amount'  => (int) round($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
            'metadata'    => $metadata,
        ]);

        return $session->url;
    }

    /**
     * Retrieve a Checkout Session to verify payment status.
     */
    public function retrieveCheckoutSession(string $sessionId): \Stripe\Checkout\Session
    {
        return $this->client->checkout->sessions->retrieve($sessionId);
    }
}
