<?php

namespace App\Console\Commands;

use App\Models\EmailQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Empties the outbox.
 *
 * Runs every minute from the scheduler, so the only process mail depends on is
 * the one the app already needs for reminders and reconciliation. There is no
 * separate daemon to forget to start.
 */
class SendQueuedEmails extends Command
{
    protected $signature = 'emails:send
                            {--limit=50 : How many to attempt in one pass}
                            {--id= : Send one specific email by id, ignoring its schedule}';

    protected $description = 'Send the emails waiting in the outbox';

    public function handle(): int
    {
        if (config('mail.default') === 'log') {
            $this->warn('mail.default is "log" — writing to the log rather than sending.');
        }

        $this->releaseAbandoned();

        $emails = $this->option('id')
            ? EmailQueue::whereKey($this->option('id'))->get()
            : EmailQueue::due()->limit((int) $this->option('limit'))->get();

        if ($emails->isEmpty()) {
            $this->info('Nothing waiting.');

            return self::SUCCESS;
        }

        $sent = $failed = 0;

        foreach ($emails as $email) {
            if (! $this->claim($email)) {
                // Another worker got there first.
                continue;
            }

            $this->deliver($email) ? $sent++ : $failed++;
        }

        $this->info("Sent {$sent}, failed {$failed}.");

        return self::SUCCESS;
    }

    /**
     * Take ownership of one email.
     *
     * The conditional update is the lock: two senders running at once cannot
     * both move the same row out of pending, so nobody is emailed twice.
     */
    private function claim(EmailQueue $email): bool
    {
        $claimed = DB::table('email_queues')
            ->where('id', $email->id)
            ->where('status', EmailQueue::PENDING)
            ->update([
                'status'      => EmailQueue::SENDING,
                'reserved_at' => now(),
                'attempts'    => DB::raw('attempts + 1'),
                'updated_at'  => now(),
            ]);

        if ($claimed === 0) {
            return false;
        }

        $email->refresh();

        return true;
    }

    private function deliver(EmailQueue $email): bool
    {
        try {
            Mail::html($email->body_html, function ($message) use ($email) {
                $message->to($email->to_address, $email->to_name)
                    ->subject($email->subject)
                    ->from($email->from_address, $email->from_name);
            });

            $email->markSent('Accepted by ' . config('mail.mailers.' . config('mail.default') . '.host', config('mail.default')));
            $this->line("  sent    #{$email->id} {$email->type} → {$email->to_address}");

            return true;
        } catch (\Throwable $e) {
            $email->markFailed($e->getMessage());

            $this->line("  failed  #{$email->id} {$email->type} → {$email->to_address}: " . mb_substr($e->getMessage(), 0, 90));

            return false;
        }
    }

    /**
     * Un-stick anything a worker claimed and then died holding, so a crashed
     * pass does not strand mail in "sending" for good.
     */
    private function releaseAbandoned(): void
    {
        EmailQueue::where('status', EmailQueue::SENDING)
            ->where('reserved_at', '<', now()->subMinutes(10))
            ->update(['status' => EmailQueue::PENDING, 'reserved_at' => null]);
    }
}
