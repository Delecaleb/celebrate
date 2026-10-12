<?php

namespace App\Support;

use App\Models\Celebration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * A celebration's video, from upload to the file the page plays.
 *
 * Nothing is converted here. The app trims the video to a minute and shrinks
 * it to a small MP4 on the phone, and takes a still for the poster, so what
 * arrives is already the finished article: it is checked against the limits
 * in config/video.php, stored, and published at once.
 *
 *   accept()  the upload arrives → ready
 *   remove()  the owner takes it down
 */
class CelebrationVideos
{
    public const READY = 'ready';

    /**
     * Store a new upload, replacing whatever was there.
     *
     * `$seconds` is the length the app measured, used only when the file
     * itself does not say.
     *
     * @throws ValidationException when the video runs longer than allowed
     */
    public function accept(Celebration $celebration, UploadedFile $file, ?UploadedFile $poster = null, ?float $seconds = null): void
    {
        $length = Mp4::seconds($file->getRealPath()) ?? $seconds;

        $this->ensureShortEnough($length);

        $this->deleteFiles($celebration);

        $name = Str::uuid()->toString();

        $celebration->forceFill([
            'intro_video'         => $file->storeAs('covers/videos', $name . '.mp4', 'public'),
            // Posters are shown at the page's width, never larger.
            'intro_video_poster'  => $poster ? Images::store($poster, 'covers/videos', ['max' => 1280, 'quality' => 78]) : null,
            'intro_video_status'  => self::READY,
            'intro_video_seconds' => $length !== null ? (int) round($length) : null,
        ])->save();
    }

    public function remove(Celebration $celebration): void
    {
        $this->deleteFiles($celebration);

        $celebration->forceFill([
            'intro_video'         => null,
            'intro_video_poster'  => null,
            'intro_video_status'  => null,
            'intro_video_seconds' => null,
        ])->save();
    }

    /** Every file a celebration's video owns. Also called when the celebration itself is deleted. */
    public function deleteFiles(Celebration $celebration): void
    {
        Storage::disk('public')->delete(array_filter([$celebration->intro_video, $celebration->intro_video_poster]));
    }

    /**
     * @throws ValidationException
     */
    private function ensureShortEnough(?float $seconds): void
    {
        $max = (int) config('video.max_seconds');

        if ($max <= 0 || $seconds === null || $seconds <= $max + (int) config('video.grace_seconds')) {
            return;
        }

        throw ValidationException::withMessages([
            'video' => self::tooLongMessage($seconds),
        ]);
    }

    public static function tooLongMessage(float $seconds): string
    {
        $max = (int) config('video.max_seconds');
        $limit = $max % 60 === 0 ? intdiv($max, 60) . ' minute' . ($max === 60 ? '' : 's') : $max . ' seconds';
        $minutes = max(1, (int) round($seconds / 60));

        return "That video is about {$minutes} minute" . ($minutes === 1 ? '' : 's')
            . " long. Videos can be up to {$limit} — trim it and try again.";
    }
}
