<?php

namespace App\Support;

use App\Models\EmailQueue;
use Illuminate\Support\Facades\Schema;

/**
 * Whether anything is actually draining the queue.
 *
 * Gift and contribution mail is queued rather than sent inside the payment
 * request, which is right — nobody should wait on an SMTP handshake to find
 * out their card went through. The cost is that with no worker running the
 * mail does not fail, it simply never happens: the admin Test button sends
 * straight away and reports success, while every real receipt sits in the
 * jobs table untouched.
 *
 * This turns that silence into something the panel can say out loud.
 */
final class QueueHealth
{
    /** Old enough that a running worker would certainly have taken it. */
    private const STALE_SECONDS = 300;

    /**
     * @return array{driver: string, pending: int, stale: int, failed: int, oldest: ?int, healthy: bool}
     */
    public static function check(): array
    {
        $state = [
            'driver'  => 'outbox',
            'pending' => 0,
            'stale'   => 0,
            'failed'  => 0,
            'oldest'  => null,
            'healthy' => true,
        ];

        if (! Schema::hasTable('email_queues')) {
            return $state;
        }

        try {
            $state['pending'] = EmailQueue::where('status', EmailQueue::PENDING)->count();
            $state['stale']   = EmailQueue::stalled((int) (self::STALE_SECONDS / 60))->count();
            $state['failed']  = EmailQueue::where('status', EmailQueue::FAILED)->count();
            $state['oldest']  = EmailQueue::stalled((int) (self::STALE_SECONDS / 60))
                ->min('created_at');
        } catch (\Throwable) {
            // A health check must never be the thing that breaks the page.
            return $state;
        }

        $state['healthy'] = $state['stale'] === 0;

        return $state;
    }
}
