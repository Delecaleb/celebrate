<?php

namespace App\Console\Commands;

use App\Mail\CelebrationReportMail;
use App\Models\Celebration;
use App\Support\Outbox;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * The report on a celebration, once the day is over.
 *
 * This replaced a weekly and a monthly digest. A celebration is one event with
 * an end, so the report belongs at that end rather than on a calendar cycle
 * that mostly reported nothing.
 *
 * Sent once. Sent again only when something new has arrived since — a gift, a
 * contribution, a wish — because people do keep giving for days afterwards and
 * a celebrant should hear about it. A page nobody touched again is never
 * emailed twice.
 */
class SendCelebrationReports extends Command
{
    protected $signature = 'mail:event-reports
                            {--date= : Treat this date as today, for testing}';

    protected $description = 'Send each finished celebration its report, and re-send when more arrives';

    public function handle(): int
    {
        $today = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $sent  = 0;

        $celebrations = Celebration::with(['user', 'gifts.platformGift', 'contributions.wish', 'comments'])
            ->where('status', 'published')
            ->get();

        foreach ($celebrations as $celebration) {
            if (! $this->hasFinished($celebration, $today)) {
                continue;
            }

            if (! $celebration->user?->email) {
                continue;
            }

            $activityAt = $this->lastActivityAt($celebration);
            $reportedAt = $celebration->last_report_sent_at;

            // Nothing happened and nobody looked: there is no report to write.
            if (! $activityAt && (int) $celebration->view_count === 0) {
                continue;
            }

            // Already told them, and nothing has happened since.
            if ($reportedAt && (! $activityAt || $activityAt->lte($reportedAt))) {
                continue;
            }

            Outbox::queue(
                new CelebrationReportMail($celebration, isUpdate: $reportedAt !== null),
                $celebration->user->email,
                'celebration.report',
                [
                    'celebration_id' => $celebration->id,
                    'update'         => $reportedAt !== null,
                ],
                $celebration->user->first_name,
            );

            $celebration->forceFill(['last_report_sent_at' => now()])->save();

            $sent++;
            $this->line(($reportedAt ? '  update  ' : '  report  ') . $celebration->title . ' → ' . $celebration->user->email);
        }

        $this->info("Celebration reports sent: {$sent}");

        return self::SUCCESS;
    }

    /**
     * Whether the day is behind us.
     *
     * The end date when there is one, otherwise the day itself — and the report
     * waits until that day is fully over, so it is not written at breakfast
     * while the party is still to come.
     */
    private function hasFinished(Celebration $celebration, Carbon $today): bool
    {
        $ends = $celebration->end_date ?? $celebration->celebrationDate();

        return $ends !== null && $ends->lt($today->copy()->startOfDay());
    }

    /** The last time anything at all happened on this page. */
    private function lastActivityAt(Celebration $celebration): ?Carbon
    {
        return collect([
            $celebration->gifts->where('payment_status', 'paid')->max('created_at'),
            $celebration->contributions->where('payment_status', 'paid')->max('created_at'),
            $celebration->comments->max('created_at'),
        ])
            ->filter()
            ->map(fn ($date) => Carbon::parse($date))
            ->max();
    }
}
