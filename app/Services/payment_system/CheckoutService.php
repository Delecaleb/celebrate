<?php

namespace App\Services\PaymentSystem;

use App\Models\User;

class CheckoutService
{
    public function __construct(
        private StripeService   $stripe,
        private PaystackService $paystack,
    ) {}

    /**
     * Route a payment to the correct gateway based on the requested currency.
     *
     * USD → Stripe (returns client_secret for frontend).
     * All others (e.g. NGN) → Paystack (returns authorization_url for redirect).
     *
     * @param  User   $user     The recipient user (celebration owner)
     * @param  float  $amount   Amount in the payment currency (no conversion)
     * @param  string $currency The payment currency (USD or local currency)
     * @return array{provider: string, currency: string, ...}
     */
    public function process(User $user, float $amount, string $currency, array $metadata = []): array
    {
        $currency = strtoupper($currency);

        if ($currency === 'USD') {
            $clientSecret = $this->stripe->createPaymentIntent($amount, 'USD', $metadata);

            return [
                'provider'      => 'stripe',
                'currency'      => 'USD',
                'client_secret' => $clientSecret,
            ];
        }

        // Use Paystack directly in the requested local currency with no conversion
        $txn = $this->paystack->initTransaction($amount, $currency, $metadata);

        return [
            'provider'          => 'paystack',
            'currency'          => $currency,
            'authorization_url' => $txn['authorization_url'],
            'reference'         => $txn['reference'],
        ];
    }
}
