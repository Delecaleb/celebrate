<?php

namespace App\Support;

/**
 * How large an uploaded image may be.
 *
 * One number, used by the validation rules, the error messages and the browser
 * check before upload, so the three can never disagree. Laravel's max rule
 * counts files in kilobytes; people count them in megabytes, so label() is what
 * anyone actually reads.
 */
final class UploadLimits
{
    /** Celebration cover photos and registry images: 10MB. */
    public const IMAGE_KB = 10240;

    /** The rule string, e.g. "max:10240". */
    public static function imageRule(): string
    {
        return 'max:' . self::IMAGE_KB;
    }

    /** The same limit in bytes, which is what File.size reports in a browser. */
    public static function bytes(int $kb = self::IMAGE_KB): int
    {
        return $kb * 1024;
    }

    /** "10MB", or "1.5MB" for a limit that is not a whole number of megabytes. */
    public static function label(int $kb = self::IMAGE_KB): string
    {
        $mb = $kb / 1024;

        return (fmod($mb, 1) === 0.0 ? (string) (int) $mb : (string) round($mb, 1)) . 'MB';
    }
}
