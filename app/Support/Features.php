<?php

namespace App\Support;

/**
 * Feature switches from config/features.php, as overlaid by the admin panel.
 *
 * The panel stores a toggle as "1" or "0" and .env gives "true" or "false", so
 * every flag is read through one boolean parse rather than a truthiness check —
 * the string "0" is truthy in PHP.
 */
final class Features
{
    /** Whether celebrants may pick a frame for their page. */
    public static function framesEnabled(): bool
    {
        return self::flag('features.frames', false);
    }

    private static function flag(string $key, bool $default): bool
    {
        $value = config($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
