<?php

namespace App\Services\PaymentSystem;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaystackService
{
    /**
     * Enough of Paystack's bank list to keep the payout form usable when their
     * API is unreachable or no key is configured (local dev, CI). The live list
     * from banks() replaces it the moment a call succeeds — codes are theirs.
     */
    private const FALLBACK_BANKS = [
        ['name' => 'Access Bank',      'code' => '044'],
        ['name' => 'Ecobank',          'code' => '050'],
        ['name' => 'Fidelity Bank',    'code' => '070'],
        ['name' => 'First Bank',       'code' => '011'],
        ['name' => 'FCMB',             'code' => '214'],
        ['name' => 'GTBank',           'code' => '058'],
        ['name' => 'Heritage Bank',    'code' => '030'],
        ['name' => 'Keystone Bank',    'code' => '082'],
        ['name' => 'Kuda Bank',        'code' => '50211'],
        ['name' => 'Moniepoint MFB',   'code' => '50515'],
        ['name' => 'OPay',             'code' => '999992'],
        ['name' => 'PalmPay',          'code' => '999991'],
        ['name' => 'Polaris Bank',     'code' => '076'],
        ['name' => 'Providus Bank',    'code' => '101'],
        ['name' => 'Stanbic IBTC',     'code' => '221'],
        ['name' => 'Sterling Bank',    'code' => '232'],
        ['name' => 'UBA',              'code' => '033'],
        ['name' => 'Union Bank',       'code' => '032'],
        ['name' => 'Unity Bank',       'code' => '215'],
        ['name' => 'Wema Bank',        'code' => '035'],
        ['name' => 'Zenith Bank',      'code' => '057'],
    ];

    private string $secretKey;

    public function __construct()
    {
        // The config key is always present but null when the env var is unset,
        // so the default here never fires — coalesce instead.
        $this->secretKey = config('services.paystack.secret') ?? '';
    }

    /**
     * Whether we can talk to Paystack at all. Account verification is only
     * enforced when we can — see ResolvesBankAccounts.
     */
    public function isConfigured(): bool
    {
        return $this->secretKey !== '';
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

            // The same transaction, resumable inside our own page by
            // js.paystack.co rather than by sending the payer away. The amount
            // and currency are already fixed server-side by this call, so the
            // browser cannot alter what is charged.
            'access_code'       => $response->json('data.access_code'),
            'reference'         => $reference,
        ];
    }

    /**
     * The public key the inline checkout needs.
     *
     * Safe to render into a page — it can only start a payment, never read or
     * move money.
     */
    public function publicKey(): ?string
    {
        return config('services.paystack.public_key') ?: null;
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

    /**
     * Banks we can pay into, as [['name' => …, 'code' => …], …].
     *
     * Cached for a day — the list barely moves and every payout form needs it.
     * A failed call only holds the fallback list for a few minutes, so a blip at
     * their end doesn't leave us on it until tomorrow.
     *
     * @return array<int, array{name: string, code: string}>
     */
    public function banks(string $currency = 'NGN'): array
    {
        $currency = strtoupper($currency);
        $key      = "paystack.banks.{$currency}";

        if ($cached = Cache::get($key)) {
            return $cached;
        }

        $banks = $this->fetchBanks($currency);

        // A failed call is cached only briefly: long enough that a Paystack
        // outage cannot turn every payout form into a retry storm, short enough
        // that we pick the real list back up soon after they recover.
        if ($banks === []) {
            Cache::put($key, self::FALLBACK_BANKS, now()->addMinutes(5));

            return self::FALLBACK_BANKS;
        }

        Cache::put($key, $banks, now()->addDay());

        return $banks;
    }

    /**
     * Ask Paystack who owns an account.
     *
     * Returns the name on the account, or null when the bank does not recognise
     * the number — that is an answer, not a fault, and the caller turns it into
     * a validation error.
     *
     * @throws \RuntimeException when Paystack itself cannot be reached
     */
    public function resolveAccountName(string $accountNumber, string $bankCode): ?string
    {
        $response = Http::withToken($this->secretKey)
            ->timeout(15)
            ->get('https://api.paystack.co/bank/resolve', [
                'account_number' => $accountNumber,
                'bank_code'      => $bankCode,
            ]);

        // 400/422 is Paystack saying "no such account at that bank".
        if (in_array($response->status(), [400, 422], true)) {
            return null;
        }

        if (! $response->successful() || ! $response->json('status')) {
            throw new \RuntimeException('Paystack account lookup failed: ' . $response->body());
        }

        $name = trim((string) $response->json('data.account_name'));

        return $name === '' ? null : $name;
    }

    /**
     * @return array<int, array{name: string, code: string}>
     */
    private function fetchBanks(string $currency): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        try {
            $response = Http::withToken($this->secretKey)
                ->timeout(15)
                ->get('https://api.paystack.co/bank', [
                    'currency' => $currency,
                    'perPage'  => 200,
                ]);
        } catch (\Throwable $e) {
            report($e);

            return [];
        }

        if (! $response->successful() || ! $response->json('status')) {
            return [];
        }

        return collect($response->json('data', []))
            ->filter(fn ($bank) => ! empty($bank['name']) && ! empty($bank['code']))
            ->map(fn ($bank) => ['name' => $bank['name'], 'code' => (string) $bank['code']])
            ->unique('code')
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
