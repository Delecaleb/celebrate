<?php

namespace App\Support;

use Illuminate\Support\Facades\Process;

/**
 * Turns whatever was uploaded into the one video format the site serves.
 *
 * A thin layer over FFmpeg: ask what a file is, convert it, take a still from
 * it. All the numbers come from config/video.php. Nothing here touches the
 * database — ProcessCelebrationVideos decides what to convert and records how
 * it went.
 */
class VideoProcessor
{
    private ?bool $available = null;

    /**
     * Whether FFmpeg can be run at all.
     *
     * False on a host with no FFmpeg, and equally on one that forbids PHP from
     * starting programs — both mean "cannot convert here".
     */
    public function isAvailable(): bool
    {
        if ($this->available !== null) {
            return $this->available;
        }

        try {
            return $this->available =
                Process::timeout(30)->run([config('video.ffmpeg'), '-version'])->successful()
                && Process::timeout(30)->run([config('video.ffprobe'), '-version'])->successful();
        } catch (\Throwable) {
            return $this->available = false;
        }
    }

    /**
     * What a file is: how long, how big, how fast.
     *
     * @return array{seconds: float, width: int, height: int, fps: float}
     *
     * @throws \RuntimeException when it is not a video FFmpeg can read
     */
    public function probe(string $path): array
    {
        $result = Process::timeout(60)->run([
            config('video.ffprobe'), '-v', 'error',
            '-print_format', 'json', '-show_format', '-show_streams',
            $path,
        ]);

        $info  = json_decode($result->output(), true) ?: [];
        $video = collect($info['streams'] ?? [])->firstWhere('codec_type', 'video');

        if (! $result->successful() || ! $video) {
            throw new \RuntimeException('That file does not contain a video we can read.');
        }

        $width  = (int) ($video['width'] ?? 0);
        $height = (int) ($video['height'] ?? 0);

        // A phone records portrait as landscape pixels plus a "turn it" note.
        // FFmpeg applies the turn when converting, so report it turned.
        $rotation = abs((int) ($video['tags']['rotate'] ?? collect($video['side_data_list'] ?? [])->pluck('rotation')->filter()->first() ?? 0));
        if ($rotation === 90 || $rotation === 270) {
            [$width, $height] = [$height, $width];
        }

        return [
            'seconds' => (float) ($info['format']['duration'] ?? $video['duration'] ?? 0),
            'width'   => $width,
            'height'  => $height,
            'fps'     => $this->fraction($video['avg_frame_rate'] ?? $video['r_frame_rate'] ?? '0'),
        ];
    }

    /**
     * Convert to MP4 (H.264 + AAC), no larger than 720p.
     *
     * Two ways to decide how hard to compress:
     *   by quality (the default) — CRF: as many bits as the picture needs;
     *   by size — pass `video_kbps` to hold the video to a bitrate, which is
     *   how a long video is made to fit the size limit.
     *
     * @param  array{short_side?: int, video_kbps?: int, audio_kbps?: int}  $options
     *
     * @throws \RuntimeException with FFmpeg's last words when it fails
     */
    public function transcode(string $source, string $destination, array $probe, array $options = []): void
    {
        $side = (int) ($options['short_side'] ?? config('video.max_short_side'));

        // Shrink so the shorter side is at most $side, never enlarge, and keep
        // both sides even (H.264 requires it): -2 means "whatever keeps the
        // shape, rounded to even".
        $scale = $probe['width'] >= $probe['height']
            ? "scale=-2:'min({$side},ih)'"
            : "scale='min({$side},iw)':-2";

        $filters = [$scale];
        if ($probe['fps'] > config('video.max_fps') + 0.5) {
            $filters[] = 'fps=' . (int) config('video.max_fps');
        }

        $rate = isset($options['video_kbps'])
            ? [
                '-b:v', $options['video_kbps'] . 'k',
                // Hold the peaks down too, or a busy scene overshoots the budget.
                '-maxrate', (int) round($options['video_kbps'] * 1.15) . 'k',
                '-bufsize', $options['video_kbps'] * 2 . 'k',
            ]
            : ['-crf', (string) config('video.crf')];

        $audio = isset($options['audio_kbps']) ? $options['audio_kbps'] . 'k' : config('video.audio_bitrate');
        $trim  = (int) config('video.max_seconds') > 0 ? ['-t', (string) config('video.max_seconds')] : [];

        $result = Process::timeout((int) config('video.timeout'))->run([
            config('video.ffmpeg'), '-y', '-hide_banner', '-loglevel', 'error',
            '-i', $source,
            ...$trim,
            '-vf', implode(',', $filters),
            '-c:v', 'libx264',
            '-preset', config('video.preset'),
            ...$rate,
            // The pixel format every phone and browser can decode.
            '-pix_fmt', 'yuv420p',
            '-c:a', 'aac', '-b:a', $audio,
            // Puts the index at the front, so playback starts before the
            // whole file has arrived.
            '-movflags', '+faststart',
            // Drop location and device details the phone embedded.
            '-map_metadata', '-1',
            $destination,
        ]);

        if (! $result->successful() || ! is_file($destination) || filesize($destination) === 0) {
            throw new \RuntimeException('FFmpeg could not convert the video: ' . $this->lastLine($result->errorOutput()));
        }
    }

    /**
     * A JPG still, taken just after the start (the very first frame is often
     * black or mid-fade).
     */
    public function poster(string $video, string $destination, float $seconds): void
    {
        $at = $seconds > 1.5 ? '0.8' : '0';

        $result = Process::timeout(60)->run([
            config('video.ffmpeg'), '-y', '-hide_banner', '-loglevel', 'error',
            '-ss', $at, '-i', $video,
            '-frames:v', '1', '-q:v', '3',
            $destination,
        ]);

        if (! $result->successful() || ! is_file($destination)) {
            throw new \RuntimeException('FFmpeg could not take a still from the video: ' . $this->lastLine($result->errorOutput()));
        }
    }

    /** "30000/1001" → 29.97 */
    private function fraction(string $value): float
    {
        [$top, $bottom] = array_pad(explode('/', $value, 2), 2, '1');

        return (float) $bottom > 0 ? (float) $top / (float) $bottom : 0.0;
    }

    private function lastLine(string $output): string
    {
        $lines = array_filter(array_map('trim', explode("\n", $output)));

        return mb_substr((string) end($lines) ?: 'no details', 0, 200);
    }
}
