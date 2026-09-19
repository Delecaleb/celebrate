<?php

namespace App\Console\Commands;

use App\Mail\CelebrationDayMail;
use App\Mail\CelebrationTwoDaysMail;
use App\Models\Celebration;
use App\Models\EmailQueue;
use App\Support\Outbox;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * The two emails that bracket the day itself.
 *
 * Two days out, while there is still time to change anything and to get the
 * link in front of people; and the morning of, to make sure the page is set up
 * and the link goes where people actually are.
 *
 * Separate from mail:celebration-countdowns, which is a scoreboard at 7, 3 and
 * 1 days. These two are instructions.
 */
class SendCelebrationNotices extends Command
{
    protected $signature = 'mail:celebration-notices
                            {--date= : Treat this date as today, for testing}';

    protected $description = 'Send the two-days-to-go and day-of emails to celebration owners';

    public function handle(): int
    {
        $today = $this->option('date') ? Carbon::parse($this->option('date')) : now();

        $sent = $this->pass(
            $today->copy()->addDays(2),
            'celebration.two-days',
            fn (Celebration $celebration) => new CelebrationTwoDaysMail($celebration),
            'T-2',
        );

        $sent += $this->pass(
            $today->copy(),
            'celebration.today',
            fn (Celebration $celebration) => new CelebrationDayMail($celebration),
            'day-of',
        );

        $this->info("Celebration notices sent: {$sent}");

        return self::SUCCESS;
    }

    /**
     * One day's worth of one kind of notice.
     */
    private function pass(Carbon $day, string $type, callable $mailable, string $label): int
    {
        $sent = 0;

        $celebrations = Celebration::with('user')
            ->happeningOn($day)
            ->where('status', 'published')
            ->get();

        foreach ($celebrations as $celebration) {
            $owner = $celebration->user;

            if (! $owner?->email) {
                continue;
            }

            // The scheduler runs this once a day, but a hand-run must not send
            // anyone the same notice twice.
            if ($this->alreadySent($type, $celebration->id)) {
                continue;
            }

            Outbox::queue(
                $mailable($celebration),
                $owner->email,
                $type,
                ['celebration_id' => $celebration->id, 'date' => $day->toDateString()],
                $owner->first_name,
            );

            $sent++;
            $this->line("  {$label} sent for: {$celebration->title} → {$owner->email}");
        }

        return $sent;
    }

    private function alreadySent(string $type, int $celebrationId): bool
    {
        return EmailQueue::where('type', $type)
            ->where('metadata->celebration_id', $celebrationId)
            ->exists();
    }
}
