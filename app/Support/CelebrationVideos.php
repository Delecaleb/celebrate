<?php

namespace App\Support;

use App\Models\Celebration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A celebration's video, from upload to the file the page plays.
 *
 * Uploading only stores the file and marks it "processing" — converting is too
 * heavy to do while someone waits on a request, and on shared hosting would
 * get the request killed. The scheduler runs convert() a moment later.
 *
 * Any length is accepted. What is fixed is the size of the finished file
 * (video.max_output_mb): a long video is compressed harder, and shown smaller,
 * until it fits.
 *
 *   accept()  the upload arrives               → processing
 *   convert() FFmpeg makes the MP4 and a still → ready | failed
 *   remove()  the owner takes it down
 */
class CelebrationVideos
{
    public const PROCESSING = 'processing';
    public const READY      = 'ready';
    public const FAILED     = 'failed';

    public function __construct(private VideoProcessor $processor) {}

    /** Store a new upload, replacing whatever was there. */
    public function accept(Celebration $celebration, UploadedFile $file): void
    {
        $this->deleteFiles($celebration);

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'mp4');

        $celebration->forceFill([
            'intro_video'          => null,
            'intro_video_poster'   => null,
            'intro_video_status'   => self::PROCESSING,
            // Private: the raw upload is never served.
            'intro_video_source'   => $file->storeAs('video-uploads', Str::uuid() . '.' . $extension, 'local'),
            'intro_video_seconds'  => null,
            'intro_video_attempts' => 0,
            'intro_video_error'    => null,
        ])->save();
    }

    public function remove(Celebration $celebration): void
    {
        $this->deleteFiles($celebration);

        $celebration->forceFill([
            'intro_video'          => null,
            'intro_video_poster'   => null,
            'intro_video_status'   => null,
            'intro_video_source'   => null,
            'intro_video_seconds'  => null,
            'intro_video_attempts' => 0,
            'intro_video_error'    => null,
        ])->save();
    }

    /**
     * Convert one waiting upload. Returns the status it ended in.
     *
     * A failure that might be passing (the server was busy, FFmpeg timed out)
     * leaves it "processing" to be tried again, up to video.max_attempts.
     */
    public function convert(Celebration $celebration): string
    {
        $source = $celebration->intro_video_source;

        if (! $source || ! Storage::disk('local')->exists($source)) {
            return $this->fail($celebration, 'The uploaded video could not be found. Please upload it again.');
        }

        $celebration->increment('intro_video_attempts');
        $sourcePath = Storage::disk('local')->path($source);

        if (! $this->processor->isAvailable()) {
            return $this->publishUnconverted($celebration, $source, $sourcePath);
        }

        $name   = 'covers/videos/' . Str::uuid();
        $public = Storage::disk('public');
        $public->makeDirectory('covers/videos');
        $videoPath  = $public->path($name . '.mp4');
        $posterPath = $public->path($name . '.jpg');

        try {
            $probe   = $this->processor->probe($sourcePath);
            $seconds = (int) config('video.max_seconds') > 0
                ? min($probe['seconds'], (float) config('video.max_seconds'))
                : $probe['seconds'];

            $this->encodeToFit($sourcePath, $videoPath, $probe, $seconds);
            $this->processor->poster($videoPath, $posterPath, $seconds);
        } catch (VideoTooLong $e) {
            @unlink($videoPath);
            @unlink($posterPath);

            // Trying again would give the same answer.
            return $this->fail($celebration, $e->getMessage());
        } catch (\Throwable $e) {
            @unlink($videoPath);
            @unlink($posterPath);

            Log::warning('Celebration video conversion failed', [
                'celebration' => $celebration->id,
                'attempt'     => $celebration->intro_video_attempts,
                'error'       => $e->getMessage(),
            ]);

            if ($celebration->intro_video_attempts < config('video.max_attempts')) {
                return self::PROCESSING;
            }

            return $this->fail($celebration, 'We could not convert that video. Try a different file — MP4 works best.');
        }

        Storage::disk('local')->delete($source);

        $celebration->forceFill([
            'intro_video'         => $name . '.mp4',
            'intro_video_poster'  => $name . '.jpg',
            'intro_video_status'  => self::READY,
            'intro_video_source'  => null,
            'intro_video_seconds' => (int) round($seconds),
            'intro_video_error'   => null,
        ])->save();

        return self::READY;
    }

    /**
     * Make the video fit the size limit, whatever its length.
     *
     * First by quality: most videos come out well under the limit and look
     * their best. One that does not is given exactly the bitrate its length
     * leaves room for — and, when that is too thin for 720p, a smaller picture
     * rather than a smeared one. A short clip never reaches the second step; a
     * long one trades sharpness for fitting.
     *
     * @throws VideoTooLong when even the smallest size cannot fit
     */
    private function encodeToFit(string $source, string $destination, array $probe, float $seconds): void
    {
        $cap = (int) config('video.max_output_mb') * 1024 * 1024;

        // A budget in kilobits per second: the limit spread over the length,
        // less a little for the container, less the sound.
        $totalKbps = $seconds > 0 ? ($cap * 8 / 1000 / $seconds) * 0.94 : PHP_INT_MAX;
        $audioKbps = $totalKbps < 600 ? 64 : 128;
        $videoKbps = (int) floor($totalKbps - $audioKbps);

        $ladder   = config('video.size_ladder');
        $smallest = min($ladder);

        if ($videoKbps < $smallest) {
            throw new VideoTooLong($this->tooLongMessage($seconds));
        }

        $this->processor->transcode($source, $destination, $probe);

        if (filesize($destination) <= $cap) {
            return;
        }

        foreach ($ladder as $side => $leastKbps) {
            if ($videoKbps < $leastKbps) {
                continue;
            }

            $this->processor->transcode($source, $destination, $probe, [
                'short_side' => $side,
                'video_kbps' => $videoKbps,
                'audio_kbps' => $audioKbps,
            ]);

            if (filesize($destination) <= $cap) {
                return;
            }
        }

        throw new VideoTooLong($this->tooLongMessage($seconds));
    }

    private function tooLongMessage(float $seconds): string
    {
        $minutes = max(1, (int) round($seconds / 60));

        return "That video is about {$minutes} minute" . ($minutes === 1 ? '' : 's')
            . ' long — too long to fit in ' . config('video.max_output_mb')
            . 'MB at a quality worth watching. Trim it and try again.';
    }

    /**
     * No FFmpeg on this server. An MP4 that is already small enough is safe to
     * serve as it is; anything else would be a gamble on the visitor's phone.
     */
    private function publishUnconverted(Celebration $celebration, string $source, string $sourcePath): string
    {
        $isMp4      = strtolower(pathinfo($source, PATHINFO_EXTENSION)) === 'mp4';
        $smallEnough = filesize($sourcePath) <= config('video.max_output_mb') * 1024 * 1024;

        if (! $isMp4 || ! $smallEnough) {
            // "No FFmpeg" can be a slow start rather than an absence, so this
            // gets the same second and third chance a failed conversion does.
            if ($celebration->intro_video_attempts < config('video.max_attempts')) {
                return self::PROCESSING;
            }

            return $this->fail(
                $celebration,
                'This video needs converting, which is not available yet. Upload an MP4 of '
                . config('video.max_output_mb') . 'MB or less.'
            );
        }

        $name = 'covers/videos/' . Str::uuid() . '.mp4';
        Storage::disk('public')->put($name, fopen($sourcePath, 'r'));
        Storage::disk('local')->delete($source);

        $celebration->forceFill([
            'intro_video'        => $name,
            'intro_video_poster' => null,
            'intro_video_status' => self::READY,
            'intro_video_source' => null,
            'intro_video_error'  => null,
        ])->save();

        return self::READY;
    }

    private function fail(Celebration $celebration, string $message): string
    {
        if ($celebration->intro_video_source) {
            Storage::disk('local')->delete($celebration->intro_video_source);
        }

        $celebration->forceFill([
            'intro_video_status' => self::FAILED,
            'intro_video_source' => null,
            'intro_video_error'  => $message,
        ])->save();

        return self::FAILED;
    }

    /** Every file a celebration's video owns. Also called when the celebration itself is deleted. */
    public function deleteFiles(Celebration $celebration): void
    {
        Storage::disk('public')->delete(array_filter([$celebration->intro_video, $celebration->intro_video_poster]));

        if ($celebration->intro_video_source) {
            Storage::disk('local')->delete($celebration->intro_video_source);
        }
    }
}

/** The one failure that trying again cannot fix: there is no fitting it in. */
class VideoTooLong extends \RuntimeException {}
