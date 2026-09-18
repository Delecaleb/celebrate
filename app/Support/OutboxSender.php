<?php

namespace App\Support;

use App\Models\EmailQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Posts what the outbox holds.
 *
 * The scheduler calls this every minute, and an admin pressing "Send now" calls
 * exactly the same code — so a mail sent by hand is claimed, recorded and
 * retried on failure the same way as one sent by cron, and the two can never
 * drift apart.
 */
final class OutboxSender
{
    /** A worker that claimed an email and died is assumed dead after this. */
    private const ABANDONED_AFTER_MINUTES = 10;

    /**
     * Send everything that is due.
     *
     * @return array{sent: int, failed: int, results: array<int, array{id: int, type: string, to: string, ok: bool, error: ?string}>}
     */
    public function sendDue(int $limit = 50): array
    {
        $this->releaseAbandoned();

        return $this->send(EmailQueue::due()->limit($limit)->get());
    }

    /**
     * Send one email now, whatever its schedule says.
     *
     * A failed or held email is put back to pending first: an admin asking for
     * it to go now is the decision that it should be tried again.
     *
     * @return array{ok: bool, error: ?string}
     */
    public function sendNow(EmailQueue $email): array
    {
        if ($email->status === EmailQueue::SENT) {
            return ['ok' => false, 'error' => 'That one already went out — sending again would deliver it twice.'];
        }

        if ($email->status === EmailQueue::SENDING && ! $email->isAbandoned(self::ABANDONED_AFTER_MINUTES)) {
            return ['ok' => false, 'error' => 'That one is going out right now.'];
        }

        $email->retry();

        if (! $this->claim($email)) {
            return ['ok' => false, 'error' => 'Something else picked it up first.'];
        }

        $error = $this->deliver($email);

        return ['ok' => $error === null, 'error' => $error];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, EmailQueue>  $emails
     * @return array{sent: int, failed: int, results: array<int, array{id: int, type: string, to: string, ok: bool, error: ?string}>}
     */
    private function send($emails): array
    {
        $sent = $failed = 0;
        $results = [];

        foreach ($emails as $email) {
            // Another worker got there first.
            if (! $this->claim($email)) {
                continue;
            }

            $error = $this->deliver($email);
            $error === null ? $sent++ : $failed++;

            $results[] = [
                'id'    => $email->id,
                'type'  => (string) $email->type,
                'to'    => (string) $email->to_address,
                'ok'    => $error === null,
                'error' => $error,
            ];
        }

        return ['sent' => $sent, 'failed' => $failed, 'results' => $results];
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

    /** @return string|null the failure, or null when it went. */
    private function deliver(EmailQueue $email): ?string
    {
        try {
            Mail::html($email->body_html, function ($message) use ($email) {
                $message->to($email->to_address, $email->to_name)
                    ->subject($email->subject)
                    ->from($email->from_address, $email->from_name);
            });

            $email->markSent('Accepted by ' . config('mail.mailers.' . config('mail.default') . '.host', config('mail.default')));

            return null;
        } catch (\Throwable $e) {
            $email->markFailed($e->getMessage());

            return $e->getMessage();
        }
    }

    /**
     * Un-stick anything a worker claimed and then died holding, so a crashed
     * pass does not strand mail in "sending" for good.
     */
    public function releaseAbandoned(): void
    {
        EmailQueue::where('status', EmailQueue::SENDING)
            ->where('reserved_at', '<', now()->subMinutes(self::ABANDONED_AFTER_MINUTES))
            ->update(['status' => EmailQueue::PENDING, 'reserved_at' => null]);
    }
}
