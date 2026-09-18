<?php

namespace App\Console\Commands;

use App\Models\EmailQueue;
use App\Support\OutboxSender;
use Illuminate\Console\Command;

/**
 * Empties the outbox.
 *
 * Runs every minute from the scheduler, so the only process mail depends on is
 * the one the app already needs for reminders and reconciliation. There is no
 * separate daemon to forget to start.
 *
 * The sending itself lives in OutboxSender, which the admin panel's "Send now"
 * buttons also call — one code path, whoever asked for it.
 */
class SendQueuedEmails extends Command
{
    protected $signature = 'emails:send
                            {--limit=50 : How many to attempt in one pass}
                            {--id= : Send one specific email by id, ignoring its schedule}';

    protected $description = 'Send the emails waiting in the outbox';

    public function handle(OutboxSender $sender): int
    {
        if (config('mail.default') === 'log') {
            $this->warn('mail.default is "log" — writing to the log rather than sending.');
        }

        if ($id = $this->option('id')) {
            $email = EmailQueue::find($id);

            if (! $email) {
                $this->error("No email with id {$id}.");

                return self::FAILURE;
            }

            $outcome = $sender->sendNow($email);

            $outcome['ok']
                ? $this->line("  sent    #{$email->id} {$email->type} → {$email->to_address}")
                : $this->line("  failed  #{$email->id} {$email->type} → {$email->to_address}: " . mb_substr((string) $outcome['error'], 0, 90));

            return self::SUCCESS;
        }

        $outcome = $sender->sendDue((int) $this->option('limit'));

        if ($outcome['results'] === []) {
            $this->info('Nothing waiting.');

            return self::SUCCESS;
        }

        foreach ($outcome['results'] as $result) {
            $result['ok']
                ? $this->line("  sent    #{$result['id']} {$result['type']} → {$result['to']}")
                : $this->line("  failed  #{$result['id']} {$result['type']} → {$result['to']}: " . mb_substr((string) $result['error'], 0, 90));
        }

        $this->info("Sent {$outcome['sent']}, failed {$outcome['failed']}.");

        return self::SUCCESS;
    }
}
