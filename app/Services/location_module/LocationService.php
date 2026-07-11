<?php

namespace App\Services\LocationModule;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationService
{
    private string $ipinfoToken;

    public function __construct()
    {
        $this->ipinfoToken = config('services.ipinfo.token', '');
    }

    /**
     * Resolve ISO 3166-1 alpha-2 country code from an IP address.
     * Uses ipinfo.io (primary) with ip-api.com as fallback.
     * Results are cached for 24 hours.
     */
    public function getCountryFromIp(string $ip): ?string
    {
        if ($this->isLocalIp($ip)) {
            return null;
        }

        return Cache::remember("location_country_{$ip}", now()->addDay(), function () use ($ip) {
            return $this->fetchFromIpinfo($ip) ?? $this->fetchFromIpApi($ip);
        });
    }

    /**
     * Resolve currency code from an IP address.
     */
    public function getCurrencyFromIp(string $ip): string
    {
        return $this->getCurrencyForCountry($this->getCountryFromIp($ip));
    }

    /**
     * Map a country code to the platform's supported currency.
     * Nigeria (NG) → NGN, everything else → USD.
     */
    public function getCurrencyForCountry(?string $countryCode): string
    {
        if (! $countryCode) {
            return config('currency.base', 'USD');
        }

        return strtoupper($countryCode) === 'NG' ? 'NGN' : 'USD';
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

    private function fetchFromIpinfo(string $ip): ?string
    {
        try {
            $headers = ['Accept' => 'application/json'];

            if ($this->ipinfoToken) {
                $headers['Authorization'] = "Bearer {$this->ipinfoToken}";
            }

            $response = Http::timeout(3)->withHeaders($headers)
                ->get("https://ipinfo.io/{$ip}/json");

            if ($response->successful()) {
                return $response->json('country') ?: null;
            }
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

    private function isLocalIp(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1'], true)
            || str_starts_with($ip, '192.168.')
            || str_starts_with($ip, '10.')
            || str_starts_with($ip, '172.');
    }
}
