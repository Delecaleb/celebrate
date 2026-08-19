<?php

namespace App\Support;

/**
 * Absolute URLs for stored media.
 *
 * Blade gets away with asset('storage/'.$path) because the browser resolves it
 * against the current host. The mobile client has no such base, so every path
 * an API resource hands out has to be fully qualified.
 */
class Media
{
    /**
     * Turn a stored relative path into an absolute URL.
     *
     * Already-absolute values (seeded remote images, gift icons pointing at a
     * CDN) are passed straight through.
     */
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    /**
     * Map a list of stored paths, dropping anything blank.
     *
     * @param  iterable<string|null>  $paths
     * @return array<int, string>
     */
    public static function urls(iterable $paths): array
    {
        $out = [];

        foreach ($paths as $path) {
            if ($url = self::url($path)) {
                $out[] = $url;
            }
        }

        return $out;
    }
}
