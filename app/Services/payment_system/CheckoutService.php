<?php

namespace App\Services\PaymentSystem;

use App\Models\User;

class CheckoutService
{
    public function __construct(
        private StripeService   $stripe,
        private PaystackService $paystack,
        private CurrencyService $currency,
    ) {}

    /**
     * Route a payment to the correct gateway based on the user's currency.
     *
     * USD users → Stripe (returns client_secret for frontend).
     * All others → Paystack (returns authorization_url for redirect).
     *
     * @param  float $amount  Amount in the user's base currency (USD)
     * @return array{provider: string, currency: string, ...}
     */
    public function process(User $user, float $amount, array $metadata = []): array
    {
        $userCurrency = $this->currency->forUser($user);

        if (strtoupper($userCurrency) === 'USD') {
            $clientSecret = $this->stripe->createPaymentIntent($amount, 'USD', $metadata);

            return [
                'provider'      => 'stripe',
                'currency'      => 'USD',
                'client_secret' => $clientSecret,
            ];
        }

        // Convert base amount (USD) to user's local currency for Paystack
        $localAmount = $this->currency->convert($amount, config('currency.base'), $userCurrency);
        $txn         = $this->paystack->initTransaction($localAmount, $userCurrency, $metadata);

        return [
            'provider'          => 'paystack',
            'currency'          => $userCurrency,
            'authorization_url' => $txn['authorization_url'],
            'reference'         => $txn['reference'],
        ];
    }
}
