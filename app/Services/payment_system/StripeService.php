<?php

namespace App\Services\PaymentSystem;

use Illuminate\Support\Facades\Log;
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

    /**
     * Confirm that Stripe took this payment, for this exact reference.
     *
     * Success-URL handlers must not credit anything until this returns true.
     * It answers false — never throws, never "assumes paid" — when the session
     * id is missing, when Stripe cannot be reached, when the session belongs to
     * a different reference, or when it simply was not paid.
     *
     * The reference check matters: without it a genuine session id from any
     * cheap payment could be replayed against someone else's reference.
     */
    public function confirmPaidFor(?string $sessionId, string $reference): bool
    {
        if (! $sessionId) {
            Log::warning('Stripe callback with no session id', ['reference' => $reference]);
            return false;
        }

        try {
            $session = $this->retrieveCheckoutSession($sessionId);
        } catch (\Throwable $e) {
            Log::error('Stripe session retrieval failed', [
                'reference' => $reference,
                'session'   => $sessionId,
                'error'     => $e->getMessage(),
            ]);
            return false;
        }

        if (($session->metadata->reference ?? null) !== $reference) {
            Log::warning('Stripe session does not match its reference', [
                'reference'         => $reference,
                'session'           => $sessionId,
                'session_reference' => $session->metadata->reference ?? null,
            ]);
            return false;
        }

        return $session->payment_status === 'paid';
    }
}
