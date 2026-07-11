<?php

namespace App\Console\Commands;

use App\Mail\CelebrationCountdownMail;
use App\Models\Celebration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendCelebrationCountdowns extends Command
{
    protected $signature   = 'mail:celebration-countdowns';
    protected $description = 'Send countdown emails to celebration owners at 7, 3, and 1 day(s) before the event';

    public function handle(): int
    {
        $countdownDays = [7, 3, 1];
        $sent          = 0;

        foreach ($countdownDays as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            $celebrations = Celebration::with(['user', 'gifts', 'wishes'])
                ->whereDate('event_date', $targetDate)
                ->where('status', 'published')
                ->get();

            foreach ($celebrations as $celebration) {
                $owner = $celebration->user;

                Mail::to($owner->email)->queue(
                    new CelebrationCountdownMail($celebration, $days)
                );

                $sent++;
                $this->line("  T-{$days} countdown sent for: {$celebration->title} → {$owner->email}");
            }
        }

        $this->info("Countdown emails sent: {$sent}");

        return self::SUCCESS;
    }
}
