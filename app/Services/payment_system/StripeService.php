<?php

namespace App\Services\PaymentSystem;

use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class StripeService
{
    private ?StripeClient $stripe = null;

    /**
     * Whether a key is configured at all.
     *
     * The client used to be built in the constructor, which meant that on a
     * box with no Stripe key every controller that injects this service threw
     * before it ran a line — including ones that only ever use Paystack.
     */
    public function isConfigured(): bool
    {
        return (string) config('services.stripe.secret') !== '';
    }

    /**
     * The API client, built on first use.
     *
     * @throws \RuntimeException when no key is configured
     */
    private function client(): StripeClient
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Stripe is not configured: set STRIPE_SECRET_KEY.');
        }

        return $this->stripe ??= new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe PaymentIntent and return the client secret.
     */
    public function createPaymentIntent(float $amount, string $currency, array $metadata = []): string
    {
        $intent = $this->client()->paymentIntents->create([
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
        $session = $this->client()->checkout->sessions->create([
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
        return $this->client()->checkout->sessions->retrieve($sessionId);
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
    /**
     * Find a recent Checkout session by the reference we put in its metadata.
     *
     * Reconciliation needs this: it only has our reference, never the session
     * id Stripe minted. Stripe cannot filter a list by metadata, so this pages
     * back through recent sessions — bounded by $sinceDays, which keeps it to
     * a handful of requests at our volume.
     */
    public function findSessionByReference(string $reference, int $sinceDays = 7): ?\Stripe\Checkout\Session
    {
        $after = null;

        for ($page = 0; $page < 10; $page++) {
            $params = [
                'limit'   => 100,
                'created' => ['gte' => now()->subDays($sinceDays)->timestamp],
            ];

            if ($after) {
                $params['starting_after'] = $after;
            }

            $sessions = $this->client()->checkout->sessions->all($params);

            foreach ($sessions->data as $session) {
                if (($session->metadata->reference ?? null) === $reference) {
                    return $session;
                }
            }

            if (! $sessions->has_more || $sessions->data === []) {
                return null;
            }

            $after = end($sessions->data)->id;
        }

        return null;
    }

    public function confirmPaidFor(?string $sessionId, string $reference): bool
    {
        if (! $sessionId) {
            // No session id — a browser callback that lost it, or the
            // reconciliation sweep. Look it up by reference instead of
            // refusing outright, but never credit on a failed lookup.
            try {
                $found = $this->findSessionByReference($reference);
            } catch (\Throwable $e) {
                Log::warning('Stripe session lookup by reference failed', [
                    'reference' => $reference,
                    'error'     => $e->getMessage(),
                ]);

                return false;
            }

            if (! $found) {
                return false;
            }

            return ($found->payment_status ?? null) === 'paid'
                && ($found->metadata->reference ?? null) === $reference;
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
