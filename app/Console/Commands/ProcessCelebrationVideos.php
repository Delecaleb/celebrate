<?php

namespace App\Console\Commands;

use App\Models\Celebration;
use App\Support\CelebrationVideos;
use Illuminate\Console\Command;

/**
 * Converts uploaded celebration videos, oldest first.
 *
 * Runs from the scheduler every minute, the same way the email outbox does, so
 * there is no queue worker to keep alive. It takes a couple at a time: FFmpeg
 * is the heaviest thing this app runs, and on shared hosting a burst of it is
 * what gets an account throttled.
 */
class ProcessCelebrationVideos extends Command
{
    protected $signature = 'videos:process {--limit=2 : How many to convert in one run}';

    protected $description = 'Convert uploaded celebration videos to web-ready MP4 and take their poster image';

    public function handle(CelebrationVideos $videos): int
    {
        $waiting = Celebration::where('intro_video_status', CelebrationVideos::PROCESSING)
            ->whereNotNull('intro_video_source')
            ->oldest('updated_at')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($waiting->isEmpty()) {
            $this->info('No videos waiting.');

            return self::SUCCESS;
        }

        foreach ($waiting as $celebration) {
            $status = $videos->convert($celebration);

            $this->line(sprintf(
                '%s  %s%s',
                str_pad($status, 10),
                $celebration->slug,
                $status === CelebrationVideos::FAILED ? '  — ' . $celebration->intro_video_error : ''
            ));
        }

        return self::SUCCESS;
    }
}
