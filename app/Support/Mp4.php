<?php

namespace App\Support;

/**
 * Reads what an MP4 says about itself, without FFmpeg.
 *
 * An MP4 is a tree of boxes, each a size and a four-letter type. The one that
 * matters here is `moov/mvhd`, the movie header, which carries the length of
 * the whole video as a count of ticks and the number of ticks per second.
 * Dividing the two is all that is needed to know how long a video runs — and
 * that is the limit the server enforces on uploads.
 *
 * `moov` is at the front of a file written for streaming and at the back of
 * one straight off a camera, so the top level is walked box by box and the
 * large `mdat` box (the pictures themselves) is skipped by its size.
 */
final class Mp4
{
    /**
     * How long the video runs, in seconds, or null when the file does not say.
     */
    public static function seconds(string $path): ?float
    {
        $handle = @fopen($path, 'rb');
        if (! $handle) {
            return null;
        }

        try {
            $size = filesize($path) ?: 0;
            $moov = self::find($handle, 0, $size, 'moov');
            if (! $moov) {
                return null;
            }

            $mvhd = self::find($handle, $moov['start'], $moov['end'], 'mvhd');
            if (! $mvhd) {
                return null;
            }

            fseek($handle, $mvhd['start']);
            $header = fread($handle, 32);
            if (strlen($header) < 20) {
                return null;
            }

            $version = ord($header[0]);

            if ($version === 1) {
                // 1 version + 3 flags + 8 created + 8 modified, then 4 timescale + 8 duration.
                $fields = unpack('Ntimescale/Jduration', substr($header, 20, 12));
            } else {
                // 1 version + 3 flags + 4 created + 4 modified, then 4 timescale + 4 duration.
                $fields = unpack('Ntimescale/Nduration', substr($header, 12, 8));
            }

            // All ones means "not known" (a file written for streaming, in
            // fragments). Then the length the app measured is used instead.
            $unknown = $version === 1 ? -1 : 0xFFFFFFFF;

            if (empty($fields['timescale']) || $fields['duration'] === $unknown || $fields['duration'] < 0) {
                return null;
            }

            return $fields['duration'] / $fields['timescale'];
        } catch (\Throwable) {
            return null;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Walk the boxes between two offsets for one of the given type.
     *
     * @return array{start: int, end: int}|null  the box's payload bounds
     */
    private static function find($handle, int $from, int $to, string $type): ?array
    {
        $offset = $from;

        // A cap on the walk, so a malformed file cannot keep this busy.
        for ($i = 0; $i < 256 && $offset + 8 <= $to; $i++) {
            fseek($handle, $offset);
            $head = fread($handle, 8);
            if (strlen($head) < 8) {
                return null;
            }

            ['size' => $size, 'type' => $boxType] = unpack('Nsize/a4type', $head);
            $headerLength = 8;

            if ($size === 1) {
                // A 64-bit size follows the header: the usual shape of a large mdat.
                $large = fread($handle, 8);
                if (strlen($large) < 8) {
                    return null;
                }
                $size = unpack('J', $large)[1];
                $headerLength = 16;
            } elseif ($size === 0) {
                // "To the end of the file."
                $size = $to - $offset;
            }

            if ($size < $headerLength) {
                return null;
            }

            if ($boxType === $type) {
                return ['start' => $offset + $headerLength, 'end' => min($to, $offset + $size)];
            }

            $offset += $size;
        }

        return null;
    }
}
