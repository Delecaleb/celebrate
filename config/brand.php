<?php

/**
 * Brand palette for PHP/Blade.
 *
 * Thin adapter over resources/brand.json — the single source of truth shared
 * with tailwind.config.js. Change colours THERE, never here.
 *
 * Read it as config('brand.primary.500'), or let <x-brand-tokens /> emit the
 * whole palette as CSS custom properties. `php artisan config:cache` freezes
 * this, so run `php artisan config:clear` after editing the JSON in production.
 */

$path = __DIR__ . '/../resources/brand.json';
$brand = is_file($path)
    ? json_decode((string) file_get_contents($path), true)
    : null;

if (! is_array($brand)) {
    throw new RuntimeException("Unable to read the brand palette at {$path}.");
}

unset($brand['$comment']);

return $brand;
