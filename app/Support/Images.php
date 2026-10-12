<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores an uploaded image at a size worth serving.
 *
 * A phone photo is 4000 pixels across and several megabytes; the page shows it
 * at a few hundred and a visitor on mobile data pays for the rest. So every
 * image that is kept — cover photos, registry pictures, wall photos, avatars,
 * video posters — goes through here: shrunk so its longer side is at most
 * `max` pixels, turned the right way up, and saved again as a JPEG at a
 * quality nobody can tell from the original. A PNG with transparency stays a
 * PNG; an animated GIF is kept as it is, since re-saving it would freeze it.
 *
 * Needs only GD, which every PHP has. If GD cannot read the file, or the
 * picture is too large to decode in the memory PHP allows, the upload is
 * stored untouched rather than refused.
 */
final class Images
{
    /** The longer side, in pixels, that a stored image is held to. */
    public const MAX_SIDE = 1600;

    /** JPEG quality: 82 is where a photo stops getting visibly better. */
    public const QUALITY = 82;

    /**
     * Store the image under `$dir` on the public disk and return its path.
     *
     * @param  array{max?: int, quality?: int}  $options
     */
    public static function store(UploadedFile $file, string $dir, array $options = [], string $disk = 'public'): string
    {
        $max     = (int) ($options['max'] ?? self::MAX_SIDE);
        $quality = (int) ($options['quality'] ?? self::QUALITY);

        $encoded = self::shrink($file->getRealPath(), $max, $quality);

        if ($encoded === null) {
            return $file->store($dir, $disk);
        }

        [$bytes, $extension] = $encoded;
        $path = trim($dir, '/') . '/' . Str::random(40) . '.' . $extension;

        Storage::disk($disk)->put($path, $bytes);

        return $path;
    }

    /**
     * The re-encoded image, or null to keep the original as it is.
     *
     * @return array{0: string, 1: string}|null  bytes and extension
     */
    private static function shrink(string $path, int $max, int $quality): ?array
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }

        [$width, $height, $type] = $info;

        // Animated GIFs are left alone: GD would keep only the first frame.
        if ($type === IMAGETYPE_GIF) {
            return null;
        }

        // Decoding needs about five bytes a pixel. A picture that would not
        // fit is stored as it came.
        if (! self::fitsInMemory($width, $height)) {
            return null;
        }

        $source = @imagecreatefromstring((string) file_get_contents($path));
        if (! $source) {
            return null;
        }

        try {
            // An 8-bit PNG decodes as a palette image, whose pixels are index
            // numbers rather than colours; nothing below can read those.
            if (! imageistruecolor($source)) {
                imagepalettetotruecolor($source);
            }

            $width  = imagesx($source);
            $height = imagesy($source);

            $scale = min(1, $max / max($width, $height, 1));
            $newWidth  = max(1, (int) round($width * $scale));
            $newHeight = max(1, (int) round($height * $scale));

            $transparent = $type === IMAGETYPE_PNG && self::hasTransparency($source);

            $out = imagecreatetruecolor($newWidth, $newHeight);
            if ($transparent) {
                imagealphablending($out, false);
                imagesavealpha($out, true);
                imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
            } else {
                // Anything see-through lands on white, as it would on the page.
                imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
            }

            imagecopyresampled($out, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // The full-size decode is let go before anything else is made:
            // turning a 12-megapixel photo would otherwise need a second copy
            // of it, which is more than a shared host allows.
            imagedestroy($source);
            $source = null;

            $out = self::upright($out, $path, $type);

            ob_start();
            if ($transparent) {
                imagepng($out, null, 8);
                $extension = 'png';
            } else {
                imagejpeg($out, null, $quality);
                $extension = 'jpg';
            }
            $bytes = (string) ob_get_clean();

            imagedestroy($out);

            return $bytes === '' ? null : [$bytes, $extension];
        } catch (\Throwable) {
            return null;
        } finally {
            if ($source) {
                imagedestroy($source);
            }
        }
    }

    /**
     * Turn a photo the way the camera was held. Phones save the sensor's
     * pixels as they are and note the orientation in EXIF; the note is lost
     * when the image is re-saved, so the pixels are turned instead. Done on
     * the shrunk copy, which turns as truly as the original and costs little.
     */
    private static function upright(\GdImage $image, string $path, int $type): \GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);

        $turned = match ($orientation) {
            3       => imagerotate($image, 180, 0),
            6       => imagerotate($image, -90, 0),
            8       => imagerotate($image, 90, 0),
            default => null,
        };

        if (! $turned) {
            return $image;
        }

        imagedestroy($image);

        return $turned;
    }

    /** Whether any pixel is less than fully opaque, sampled on a coarse grid. */
    private static function hasTransparency(\GdImage $image): bool
    {
        $width  = imagesx($image);
        $height = imagesy($image);
        $stepX  = max(1, intdiv($width, 64));
        $stepY  = max(1, intdiv($height, 64));

        for ($y = 0; $y < $height; $y += $stepY) {
            for ($x = 0; $x < $width; $x += $stepX) {
                if ((imagecolorat($image, $x, $y) >> 24) & 0x7F) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function fitsInMemory(int $width, int $height): bool
    {
        $limit = self::bytes((string) ini_get('memory_limit'));
        if ($limit <= 0) {
            return true;
        }

        // The decode, the file's own bytes, and room for the shrunk copy.
        $needed = $width * $height * 5 * 1.3;

        return memory_get_usage() + $needed < $limit;
    }

    /** "512M" → bytes. -1 and 0 mean no limit. */
    private static function bytes(string $value): int
    {
        $value  = trim($value);
        $number = (int) $value;

        if ($number <= 0) {
            return 0;
        }

        return $number * match (strtoupper(substr($value, -1))) {
            'G'     => 1024 ** 3,
            'M'     => 1024 ** 2,
            'K'     => 1024,
            default => 1,
        };
    }
}
