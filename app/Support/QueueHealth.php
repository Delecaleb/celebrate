<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
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
        $driver = (string) config('queue.default');

        $state = [
            'driver'  => $driver,
            'pending' => 0,
            'stale'   => 0,
            'failed'  => 0,
            'oldest'  => null,
            // "sync" runs jobs in the request, so there is no worker to miss.
            'healthy' => true,
        ];

        if ($driver !== 'database' || ! Schema::hasTable('jobs')) {
            return $state;
        }

        try {
            $cutoff = now()->subSeconds(self::STALE_SECONDS)->getTimestamp();

            $state['pending'] = (int) DB::table('jobs')->count();
            $state['stale']   = (int) DB::table('jobs')->where('created_at', '<', $cutoff)->count();
            $state['oldest']  = DB::table('jobs')->min('created_at');

            if (Schema::hasTable('failed_jobs')) {
                $state['failed'] = (int) DB::table('failed_jobs')->count();
            }
        } catch (\Throwable) {
            // A health check must never be the thing that breaks the page.
            return $state;
        }

        $state['healthy'] = $state['stale'] === 0;

        return $state;
    }
}
