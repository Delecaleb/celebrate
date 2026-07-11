<?php

namespace App\Console\Commands;

use App\Mail\CelebrationReminderMail;
use App\Models\Celebration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendCelebrationReminders extends Command
{
    protected $signature   = 'mail:celebration-reminders';
    protected $description = 'Send reminder emails for celebrations happening in 7, 3, or 1 day(s)';

    public function handle(): int
    {
        $reminderDays = [7, 3, 1];
        $sent         = 0;

        foreach ($reminderDays as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            $celebrations = Celebration::with(['user', 'guests'])
                ->whereDate('event_date', $targetDate)
                ->where('status', 'published')
                ->get();

            foreach ($celebrations as $celebration) {
                $owner = $celebration->user;

                // Notify the celebration owner
                Mail::to($owner->email)->queue(
                    new CelebrationReminderMail(
                        celebration:   $celebration,
                        recipientName: $owner->first_name,
                        recipientEmail: $owner->email,
                        daysUntil:     $days,
                        isOwner:       true,
                    )
                );
                $sent++;

                // Notify each registered guest
                foreach ($celebration->guests as $guest) {
                    if (! $guest->guest_email) {
                        continue;
                    }

                    Mail::to($guest->guest_email)->queue(
                        new CelebrationReminderMail(
                            celebration:    $celebration,
                            recipientName:  $guest->guest_name ?? 'Guest',
                            recipientEmail: $guest->guest_email,
                            daysUntil:      $days,
                            isOwner:        false,
                        )
                    );
                    $sent++;
                }

                $this->line("  Sent {$days}-day reminder for: {$celebration->title}");
            }
        }

        $this->info("Celebration reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
