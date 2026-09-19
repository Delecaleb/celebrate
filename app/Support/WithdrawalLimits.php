<?php

namespace App\Support;

/**
 * The smallest payout allowed, per currency.
 *
 * Admins set it on each currency in the panel; it reaches config the same way
 * rates and symbols do (SettingsRepository::currencyOverlay), so every caller
 * reads config rather than the database. Zero, or a currency nobody has set a
 * figure for, means no minimum.
 */
final class WithdrawalLimits
{
    public static function min(?string $currency): float
    {
        $code = strtoupper((string) ($currency ?: config('currency.base')));

        return max(0.0, (float) (config("currency.minimums.{$code}") ?? 0));
    }

    public static function allows(float $amount, ?string $currency): bool
    {
        return $amount >= self::min($currency);
    }

    /** "₦5,000.00", for a message a person reads. */
    public static function format(?string $currency): string
    {
        $code     = strtoupper((string) ($currency ?: config('currency.base')));
        $symbol   = config("currency.currencies.{$code}.symbol", '');
        $decimals = (int) config("currency.currencies.{$code}.decimals", 2);

        return $symbol . number_format(self::min($code), $decimals) . ' ' . $code;
    }

    /** The refusal, worded once so both the site and the app say the same thing. */
    public static function message(?string $currency): string
    {
        return 'The smallest withdrawal in this currency is ' . self::format($currency) . '.';
    }
}
