<?php

namespace App\Services\LocationModule;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Where a request is coming from, and therefore which currency it works in.
 *
 * The chain, in order:
 *   1. the IP, through ipinfo.io (ip-api.com as a fallback);
 *   2. currency.fallback_country, when the IP cannot tell us anything — a
 *      private address in development, an unreachable lookup, a crawler;
 *   3. currency.base, only if that fallback is cleared.
 *
 * Step 2 is the point. Without it every signup we could not geolocate became a
 * USD account, which for a platform whose customers are overwhelmingly in one
 * country is not a neutral default — it is the wrong answer most of the time.
 *
 * Nothing here names a country. Which countries get their own currency is
 * currency.country_map; which one is assumed when detection fails is
 * currency.fallback_country. Adding a market is a config edit.
 */
class LocationService
{
    private string $ipinfoToken;

    public function __construct()
    {
        $this->ipinfoToken = config('services.ipinfo.token', '') ?? '';
    }

    /**
     * Resolve ISO 3166-1 alpha-2 country code from an IP address.
     *
     * Returns null when the address cannot be placed — the caller decides what
     * that means, rather than having a country invented for it here.
     * Results are cached for 24 hours.
     */
    public function getCountryFromIp(string $ip): ?string
    {
        $lookupIp = $this->lookupAddressFor($ip);

        if (! $lookupIp) {
            return null;
        }

        return Cache::remember("location_country_{$lookupIp}", now()->addDay(), function () use ($lookupIp) {
            return $this->fetchFromIpinfo($lookupIp) ?? $this->fetchFromIpApi($lookupIp);
        });
    }

    /**
     * The currency this address works in.
     */
    public function getCurrencyFromIp(string $ip): string
    {
        return $this->getCurrencyForCountry($this->countryOrFallback($ip));
    }

    /**
     * The country to treat this address as being in.
     *
     * Detection first; the configured fallback when detection has nothing to
     * say. Exposed so callers that want to store the country — the user
     * observer, for one — record the same answer the currency came from.
     */
    public function countryOrFallback(string $ip): ?string
    {
        $detected = $this->getCountryFromIp($ip);

        if ($detected) {
            return $detected;
        }

        $fallback = trim((string) config('currency.fallback_country', ''));

        if ($fallback !== '') {
            Log::debug('LocationService: no country for IP, using the configured fallback', [
                'ip'       => $ip,
                'fallback' => $fallback,
            ]);

            return $fallback;
        }

        return null;
    }

    /**
     * Map a country code to the platform's supported currency.
     *
     * Only countries listed in currency.country_map get their own currency —
     * and with it a local wallet. Everywhere else checks out in the base
     * currency. Adding a country is a config edit.
     */
    public function getCurrencyForCountry(?string $countryCode): string
    {
        $base = config('currency.base', 'USD');

        if (! $countryCode) {
            return $base;
        }

        return config('currency.country_map.' . strtolower(trim($countryCode)), $base);
    }

    /**
     * Detect currency for the current HTTP request's IP.
     */
    public function forCurrentRequest(): string
    {
        return $this->getCurrencyFromIp(request()->ip());
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * The address to actually look up, or null if there is nothing worth asking
     * about.
     *
     * A private or reserved address cannot be geolocated. In development that
     * is every request, so services.ipinfo.dev_ip stands in for one when it is
     * set — which is how the real lookup gets exercised before deploying.
     */
    private function lookupAddressFor(string $ip): ?string
    {
        if ($this->isPublicIp($ip)) {
            return $ip;
        }

        $devIp = trim((string) config('services.ipinfo.dev_ip', ''));

        return ($devIp !== '' && $this->isPublicIp($devIp)) ? $devIp : null;
    }

    private function fetchFromIpinfo(string $ip): ?string
    {
        try {
            $headers = ['Accept' => 'application/json'];

            if ($this->ipinfoToken !== '') {
                $headers['Authorization'] = "Bearer {$this->ipinfoToken}";
            }

            $response = Http::timeout(3)->withHeaders($headers)
                ->get("https://ipinfo.io/{$ip}/json");

            if ($response->successful()) {
                return $response->json('country') ?: null;
            }

            Log::warning('LocationService: ipinfo.io returned an error', [
                'ip'     => $ip,
                'status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("LocationService: ipinfo.io lookup failed for {$ip}: {$e->getMessage()}");
        }

        return null;
    }

    private function fetchFromIpApi(string $ip): ?string
    {
        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'countryCode',
            ]);

            if ($response->successful()) {
                return $response->json('countryCode') ?: null;
            }
        } catch (\Throwable $e) {
            Log::warning("LocationService: ip-api.com fallback failed for {$ip}: {$e->getMessage()}");
        }

        return null;
    }

    /**
     * Routable on the public internet, and therefore worth a lookup.
     *
     * The previous version treated the whole of 172.0.0.0/8 as private — only
     * 172.16–31 is — so genuine visitors on those ranges were quietly given the
     * fallback. PHP already knows the real ranges.
     */
    private function isPublicIp(string $ip): bool
    {
        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
