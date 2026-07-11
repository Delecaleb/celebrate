<?php

namespace App\Services\PaymentSystem;

use App\Models\User;
use App\Services\LocationModule\LocationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyService
{
    public function __construct(private LocationService $location) {}

    // -------------------------------------------------------------------------
    // Currency detection
    // -------------------------------------------------------------------------

    /**
     * Currency for a logged-in user.
     * Reads the stored `currency` column set at registration — never re-derives from country.
     */
    public function forUser(?User $user = null): string
    {
        $user ??= auth()->user();

        // Stored at registration from IP — authoritative, never editable
        if ($user?->currency) {
            return $user->currency;
        }

        // Legacy fallback: derive from stored country
        if ($user?->country) {
            $key = strtolower(trim($user->country));
            return config("currency.country_map.{$key}", config('currency.base'));
        }

        return config('currency.base');
    }

    /**
     * Currency for the current visitor (logged-in user or anonymous guest).
     */
    public function forVisitor(): string
    {
        $user = auth()->user();

        if ($user?->currency) {
            return $user->currency;
        }

        return $this->location->getCurrencyFromIp(request()->ip());
    }

    // -------------------------------------------------------------------------
    // Amount computation — stores base (USD) + converted (local) equivalents
    // -------------------------------------------------------------------------

    /**
     * Given an amount in a known currency, compute the base (USD) and
     * converted (local) equivalents plus the rate used.
     *
     * Returns keys ready to spread into Wish::create().
     */
    public function computeAmounts(float $amount, string $currency): array
    {
        $base      = config('currency.base');
        $converted = $currency === $base ? $this->resolveConvertedCurrency() : $currency;
        $rate      = $this->fetchCachedRate($base, $converted);

        if ($currency !== $base) {
            $amountBase      = $rate > 0 ? round($amount / $rate, 2) : null;
            $amountConverted = round($amount, 2);
        } else {
            $amountBase      = round($amount, 2);
            $amountConverted = round($amount * $rate, 2);
        }

        return [
            'currency'           => $currency,
            'base_currency'      => $base,
            'amount_base'        => $amountBase,
            'converted_currency' => $converted,
            'amount_converted'   => $amountConverted,
            'conversion_rate'    => $rate,
        ];
    }

    private function resolveConvertedCurrency(): string
    {
        $visitor = $this->forVisitor();

        if ($visitor === config('currency.base')) {
            foreach (array_keys(config('currency.currencies', [])) as $code) {
                if ($code !== config('currency.base')) {
                    return $code;
                }
            }
        }

        return $visitor;
    }

    // -------------------------------------------------------------------------
    // Conversion & formatting
    // -------------------------------------------------------------------------

    public function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to || $amount === 0.0) {
            return $amount;
        }

        return round($amount * $this->fetchCachedRate($from, $to), 2);
    }

    public function getRate(string $from, string $to): float
    {
        return $this->fetchCachedRate($from, $to);
    }

    public function convertForUser(float $amount, ?string $from = null, ?User $user = null): float
    {
        $from ??= config('currency.base');
        return $this->convert($amount, $from, $this->forUser($user));
    }

    public function format(float $amount, string $currency): string
    {
        $meta     = config("currency.currencies.{$currency}");
        $symbol   = $meta['symbol']   ?? $currency;
        $decimals = $meta['decimals'] ?? 2;

        return $symbol . number_format($amount, $decimals);
    }

    public function formatForUser(float $amount, ?string $from = null, ?User $user = null): string
    {
        $from     ??= config('currency.base');
        $currency   = $this->forUser($user);

        return $this->format($this->convert($amount, $from, $currency), $currency);
    }

    public function symbol(?User $user = null): string
    {
        $currency = $this->forUser($user);
        return config("currency.currencies.{$currency}.symbol", $currency);
    }

    public function symbolForVisitor(): string
    {
        $currency = $this->forVisitor();
        return config("currency.currencies.{$currency}.symbol", $currency);
    }

    // -------------------------------------------------------------------------
    // Rate fetching — cached with live API + hardcoded fallback
    // -------------------------------------------------------------------------

    private function fetchCachedRate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $ttl = now()->addMinutes((int) config('currency.cache_ttl', 360));

        return Cache::remember("currency_rate_{$from}_{$to}", $ttl, fn () => $this->fetchRate($from, $to));
    }

    private function fetchRate(string $from, string $to): float
    {
        try {
            $apiKey = config('currency.api_key');
            $url    = $apiKey
                ? "https://v6.exchangerate-api.com/v6/{$apiKey}/latest/{$from}"
                : "https://open.er-api.com/v6/latest/{$from}";

            $response = Http::timeout(5)->get($url);

            if ($response->successful()) {
                $rate = data_get($response->json(), "rates.{$to}");
                if ($rate !== null) {
                    return (float) $rate;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("CurrencyService: live rate fetch failed ({$from}→{$to}): {$e->getMessage()}");
        }

        return $this->fallbackRate($from, $to);
    }

    private function fallbackRate(string $from, string $to): float
    {
        $base = config('currency.base');

        if ($from === $base) {
            return (float) config("currency.fallback_rates.{$to}", 1.0);
        }

        if ($to === $base) {
            $inverse = (float) config("currency.fallback_rates.{$from}", 1.0);
            return $inverse > 0 ? round(1 / $inverse, 8) : 1.0;
        }

        return 1.0;
    }
}
