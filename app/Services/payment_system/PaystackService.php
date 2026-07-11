<?php

namespace App\Services\PaymentSystem;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaystackService
{
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret', '');
    }

    /**
     * Initialize a Paystack transaction.
     *
     * @param  float  $amount   Amount in the target currency (e.g. NGN)
     * @param  string $currency ISO 4217 currency code (e.g. 'NGN')
     * @param  array  $metadata Extra metadata forwarded to Paystack
     * @return array{authorization_url: string, reference: string}
     * @throws \Exception on API failure
     */
    public function initTransaction(float $amount, string $currency, array $metadata = []): array
    {
        $reference = 'ps-' . Str::uuid();

        $response = Http::withToken($this->secretKey)
            ->post('https://api.paystack.co/transaction/initialize', [
                'email'     => $metadata['email'] ?? 'guest@example.com',
                'amount'    => (int) round($amount * 100), // kobo/cents
                'currency'  => strtoupper($currency),
                'reference' => $reference,
                'metadata'  => $metadata,
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            throw new \RuntimeException('Paystack initialization failed: ' . $response->body());
        }

        return [
            'authorization_url' => $response->json('data.authorization_url'),
            'reference'         => $reference,
        ];
    }

    /**
     * Verify a Paystack transaction by reference.
     *
     * @throws \RuntimeException on API failure
     */
    public function verifyTransaction(string $reference): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("https://api.paystack.co/transaction/verify/{$reference}");

        if (! $response->successful()) {
            throw new \RuntimeException('Paystack verification failed: ' . $response->body());
        }

        return $response->json('data', []);
    }
}
