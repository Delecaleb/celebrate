<?php

namespace App\Support;

use App\Models\EmailQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Where outgoing mail is posted.
 *
 * Replaces Mail::to(…)->queue(…). The difference is what gets stored: a row
 * that says who the mail is for, what it says and what the server answered,
 * rather than a serialised job nobody can read.
 *
 * The mailable is rendered here, at the moment of queueing, which buys two
 * things. A template that cannot render fails in front of whoever caused it
 * instead of silently in a worker minutes later; and the stored copy is
 * exactly what the recipient will get, so the outbox is evidence rather than
 * a guess.
 */
final class Outbox
{
    /**
     * Post a mailable.
     *
     * Never throws: mail is a side effect of something that already happened —
     * a payment has settled by the time a receipt is written — so a broken
     * template must not unwind it. It returns null and logs instead.
     *
     * @param  array<string, mixed>  $metadata  anything worth finding it by later
     */
    public static function queue(
        Mailable $mailable,
        string $to,
        string $type,
        array $metadata = [],
        ?string $toName = null,
        ?\DateTimeInterface $availableAt = null,
    ): ?EmailQueue {
        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Outbox: refused an unusable address', ['type' => $type, 'to' => $to]);

            return null;
        }

        try {
            $envelope = $mailable->envelope();
            $subject  = $envelope->subject ?: config('app.name');
            $body     = $mailable->render();
        } catch (\Throwable $e) {
            Log::error('Outbox: could not render a mailable', [
                'type'     => $type,
                'mailable' => $mailable::class,
                'error'    => $e->getMessage(),
            ]);

            return null;
        }

        return EmailQueue::create([
            'uuid'         => (string) Str::uuid(),
            'type'         => $type,
            'mailable'     => $mailable::class,
            'from_address' => $envelope->from?->address ?: (string) config('mail.from.address'),
            'from_name'    => $envelope->from?->name ?: (string) config('mail.from.name'),
            'to_address'   => $to,
            'to_name'      => $toName,
            'subject'      => $subject,
            'body_html'    => $body,
            'metadata'     => $metadata ?: null,
            'status'       => EmailQueue::PENDING,
            'available_at' => $availableAt,
        ]);
    }
}
