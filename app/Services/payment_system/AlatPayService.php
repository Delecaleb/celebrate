<?php

namespace App\Services\PaymentSystem;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AlatPay (Wema Bank) — naira payments.
 *
 * Payers choose for themselves: AlatPay's own checkout opens over the page with
 * card, bank transfer and USSD as tabs. It is their UI, so no card detail ever
 * reaches this application — we only say what the payment is for and wait to be
 * told it happened.
 *
 * A virtual account can still be opened directly (createVirtualAccount) for a
 * browser that cannot load their script; the payer then transfers to it and the
 * webhook settles it the same way.
 *
 * Every call carries the business id (which account the money is for) and the
 * subscription key (who is asking). Both come from the AlatPay portal and are
 * set in Settings → Payments.
 */
class AlatPayService
{
    /** AlatPay's own id for the payment, so a transfer can be matched back. */
    public const REFERENCE_PREFIX = 'alat-';

    private string $baseUrl;

    private string $subscriptionKey;

    private string $businessId;

    public function __construct()
    {
        $this->baseUrl         = rtrim((string) config('services.alatpay.base_url'), '/');
        $this->subscriptionKey = (string) (config('services.alatpay.subscription_key') ?? '');
        $this->businessId      = (string) (config('services.alatpay.business_id') ?? '');
    }

    /**
     * Whether to open AlatPay's checkout, or go straight to a transfer.
     *
     * Unset means their checkout, so an install that has never touched the
     * setting gives payers the full choice. Their script has no way to show
     * some channels and not others — that is set on the business in their
     * portal — so this is the only lever on this side.
     */
    public static function fullCheckout(): bool
    {
        $value = config('services.alatpay.full_checkout');

        if ($value === null || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
    }

    /** Whether we can talk to AlatPay at all. */
    public function isConfigured(): bool
    {
        return $this->subscriptionKey !== '' && $this->businessId !== '';
    }

    /**
     * What the browser needs to open AlatPay's own checkout.
     *
     * The popup is the whole reason a payer gets a choice: card, transfer and
     * USSD are AlatPay's tabs, rendered by AlatPay, so no card detail ever
     * touches this application. We only hand it what the payment is for.
     *
     * @param  array{email?: string, phone?: string, first_name?: string, last_name?: string}  $customer
     * @return array<string, mixed>
     */
    public function popupOptions(float $amount, string $currency, string $orderId, array $customer = []): array
    {
        return [
            'script'      => (string) config('services.alatpay.checkout_js'),
            // Their script reads publicKey; their own WooCommerce plugin sends
            // apiKey. Both are filled with the same value so either build of
            // the script finds what it wants.
            'api_key'     => (string) (config('services.alatpay.public_key') ?: $this->subscriptionKey),
            'business_id' => $this->businessId,
            'amount'      => round($amount, 2),
            'currency'    => strtoupper($currency),
            'email'       => $customer['email'] ?? 'guest@celebratemi.com',
            'first_name'  => $customer['first_name'] ?? 'Guest',
            'last_name'   => $customer['last_name'] ?? '',
            'phone'       => $customer['phone'] ?? '',
            // Comes back on the transaction, which is how a payment AlatPay
            // confirms is matched to the record it paid for.
            'metadata'    => ['order_id' => $orderId],
        ];
    }

    /**
     * Ask for a virtual account to receive one payment.
     *
     * The fallback for a browser that cannot load AlatPay's checkout script —
     * the payer transfers to this account from their own bank app instead.
     *
     * @param  array{email?: string, phone?: string, first_name?: string, last_name?: string}  $customer
     * @return array{
     *     transaction_id: string, account_number: string, bank_name: ?string,
     *     account_name: ?string, amount: float, expires_at: ?string, reference: string
     * }
     *
     * @throws \RuntimeException when AlatPay will not open one
     */
    public function createVirtualAccount(
        float $amount,
        string $currency,
        string $orderId,
        array $customer = [],
        string $description = 'CelebrateMi payment',
    ): array {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('AlatPay is not configured.');
        }

        $response = $this->request()->post($this->baseUrl . '/bank-transfer/api/v1/bankTransfer/virtualAccount', [
            'businessId'  => $this->businessId,
            'amount'      => round($amount, 2),
            'currency'    => strtoupper($currency),
            'orderId'     => $orderId,
            'description' => $description,
            'customer'    => [
                'email'     => $customer['email'] ?? 'guest@celebratemi.com',
                'phone'     => $customer['phone'] ?? '',
                'firstName' => $customer['first_name'] ?? 'Guest',
                'lastName'  => $customer['last_name'] ?? '',
                'metadata'  => json_encode(['order_id' => $orderId]),
            ],
        ]);

        if (! $response->successful() || $response->json('status') === false) {
            throw new \RuntimeException('AlatPay could not open an account for this payment: ' . $response->body());
        }

        $data = (array) ($response->json('data') ?? []);

        $accountNumber = $this->firstOf($data, ['virtualBankAccountNumber', 'accountNumber', 'virtualAccountNumber']);
        $transactionId = $this->firstOf($data, ['transactionId', 'id']);

        if (! $accountNumber || ! $transactionId) {
            throw new \RuntimeException('AlatPay answered without an account number: ' . $response->body());
        }

        return [
            'transaction_id' => (string) $transactionId,
            'account_number' => (string) $accountNumber,
            'bank_name'      => $this->firstOf($data, ['virtualBankName', 'bankName']) ?? 'Wema Bank',
            'account_name'   => $this->firstOf($data, ['virtualBankAccountName', 'accountName']),
            'amount'         => (float) ($this->firstOf($data, ['amount']) ?? $amount),
            'expires_at'     => $this->firstOf($data, ['expiredAt', 'expiresAt', 'expiryDate']),
            'reference'      => $orderId,
        ];
    }

    /**
     * What AlatPay says about one transaction.
     *
     * Used by payments:reconcile to settle a transfer whose webhook never
     * arrived. Returns null when the lookup fails — an unreachable gateway is
     * not evidence that nobody paid, so the caller must leave the payment
     * alone rather than fail it.
     *
     * @return array<string, mixed>|null
     */
    public function transactionStatus(string $transactionId): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $path = (string) config(
            'services.alatpay.status_path',
            '/alatpaytransaction/api/v1/transactions/{id}'
        );

        try {
            $response = $this->request()->get($this->baseUrl . str_replace('{id}', $transactionId, $path));

            if (! $response->successful()) {
                Log::warning('AlatPay status lookup refused', [
                    'transaction' => $transactionId,
                    'status'      => $response->status(),
                ]);

                return null;
            }

            return (array) ($response->json('data') ?? []);
        } catch (\Throwable $e) {
            Log::warning('AlatPay status lookup failed', [
                'transaction' => $transactionId,
                'error'       => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Whether one of AlatPay's status words means the money arrived.
     *
     * Written as a whitelist: anything unrecognised is treated as "not paid",
     * because crediting on a word we do not understand is the expensive
     * mistake.
     */
    public static function isPaid(?string $status): bool
    {
        return in_array(strtolower(trim((string) $status)), ['completed', 'complete', 'successful', 'success', 'paid'], true);
    }

    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => $this->subscriptionKey,
            'Accept'                    => 'application/json',
        ])->timeout(20);
    }

    /**
     * AlatPay spells some fields differently between its documentation and its
     * responses, so each is looked up by every name it is known to use.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $keys
     */
    private function firstOf(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                return (string) $data[$key];
            }
        }

        return null;
    }
}
