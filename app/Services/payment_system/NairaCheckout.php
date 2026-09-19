<?php

namespace App\Services\PaymentSystem;

use App\Support\PaymentGateways;
use Illuminate\Support\Facades\Log;

/**
 * Starting a naira payment, whichever gateway is up.
 *
 * Paystack leads when it is switched on and AlatPay stands behind it, so a
 * gateway that will not open a checkout — keys revoked, API down, an outage
 * mid-evening — hands the payer to the other one instead of an error. With
 * Paystack off, AlatPay simply is the checkout.
 *
 * The two answer differently and the difference is the point: Paystack returns
 * an access code for its card frame, AlatPay returns what its own checkout needs
 * to open — where the payer picks card, transfer or USSD. Both shapes come back
 * under `provider`, and both carry the reference every payment record must be
 * filed under.
 */
class NairaCheckout
{
    public function __construct(
        private PaystackService $paystack,
        private AlatPayService $alatpay,
    ) {}

    /**
     * @param  array{email?: string, phone?: string, first_name?: string, last_name?: string}  $customer
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>  provider, reference, and that provider's own fields
     *
     * @throws \RuntimeException when no gateway would take it
     */
    public function start(
        float $amount,
        string $currency,
        string $reference,
        array $customer = [],
        array $metadata = [],
        string $description = 'CelebrateMi payment',
    ): array {
        $failures = [];

        foreach (PaymentGateways::activeFor($currency) as $gateway) {
            try {
                return match ($gateway) {
                    PaymentGateways::PAYSTACK => $this->viaPaystack($amount, $currency, $metadata, $customer),
                    PaymentGateways::ALATPAY  => $this->viaAlatPay($amount, $currency, $reference, $customer, $description),
                    default                   => throw new \RuntimeException("No naira checkout for {$gateway}."),
                };
            } catch (\Throwable $e) {
                // Not fatal while another gateway is still to try — that is the
                // whole reason for having a second one.
                $failures[$gateway] = $e->getMessage();

                Log::warning('Checkout gateway refused, trying the next', [
                    'gateway'  => $gateway,
                    'currency' => $currency,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        throw new \RuntimeException(
            $failures === []
                ? 'No payment gateway is switched on for ' . strtoupper($currency) . '.'
                : 'Every gateway refused: ' . json_encode($failures)
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $customer
     * @return array<string, mixed>
     */
    private function viaPaystack(float $amount, string $currency, array $metadata, array $customer): array
    {
        $txn = $this->paystack->initTransaction($amount, $currency, $metadata + [
            'email' => $customer['email'] ?? ($metadata['email'] ?? 'guest@celebratemi.com'),
        ]);

        return [
            'provider' => PaymentGateways::PAYSTACK,
            // Paystack mints its own reference, so callers must move their rows
            // onto this one before the callback looks for them.
            'reference'         => $txn['reference'],
            'access_code'       => $txn['access_code'] ?? null,
            'authorization_url' => $txn['authorization_url'],
        ];
    }

    /**
     * @param  array<string, mixed>  $customer
     * @return array<string, mixed>
     */
    private function viaAlatPay(float $amount, string $currency, string $reference, array $customer, string $description): array
    {
        if (! $this->alatpay->isConfigured()) {
            throw new \RuntimeException('AlatPay is not configured.');
        }

        // Transfer-only, by choice: skip their checkout and open an account.
        if (! AlatPayService::fullCheckout()) {
            $account = $this->alatpay->createVirtualAccount($amount, $currency, $reference, $customer, $description);

            return ['provider' => PaymentGateways::ALATPAY, 'reference' => $reference] + $account;
        }

        return [
            'provider'  => PaymentGateways::ALATPAY,
            // AlatPay's checkout is opened against our own reference, so
            // nothing has to be renamed afterwards.
            'reference' => $reference,
            // Everything the browser needs to open their checkout, where the
            // payer picks whichever channel their portal has enabled.
            'checkout'  => $this->alatpay->popupOptions($amount, $currency, $reference, $customer),
        ];
    }
}
