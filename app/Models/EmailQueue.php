<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One email, and everything that happened to it.
 *
 * A row is created the moment something decides to send mail, and it is never
 * deleted by the sender — a failed email is more interesting than a sent one,
 * and both are the record of what a person was or was not told.
 */
class EmailQueue extends Model
{
    public const PENDING = 'pending';
    public const SENDING = 'sending';
    public const SENT    = 'sent';
    public const FAILED  = 'failed';
    public const HELD    = 'held';

    /** Minutes to wait before each retry: a blip clears fast, a bad address never does. */
    private const BACKOFF = [1, 10, 60];

    protected $fillable = [
        'uuid', 'type', 'mailable',
        'from_address', 'from_name', 'to_address', 'to_name',
        'subject', 'body_html', 'metadata',
        'status', 'attempts', 'max_attempts',
        'available_at', 'reserved_at',
        'response', 'last_error', 'sent_at',
    ];

    protected $casts = [
        'metadata'     => 'array',
        'attempts'     => 'integer',
        'max_attempts' => 'integer',
        'available_at' => 'datetime',
        'reserved_at'  => 'datetime',
        'sent_at'      => 'datetime',
    ];

    /* ── Scopes ─────────────────────────────────────────────────────── */

    /** Waiting, and due — the sender's whole query. */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', self::PENDING)
            ->where(fn (Builder $q) => $q
                ->whereNull('available_at')
                ->orWhere('available_at', '<=', now()))
            ->orderBy('id');
    }

    /**
     * Pending long enough that a running sender would certainly have taken it.
     * This is what tells the panel nobody is sending.
     */
    public function scopeStalled(Builder $query, int $minutes = 5): Builder
    {
        return $query->where('status', self::PENDING)
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->where(fn (Builder $q) => $q
                ->whereNull('available_at')
                ->orWhere('available_at', '<=', now()->subMinutes($minutes)));
    }

    /* ── Transitions ────────────────────────────────────────────────── */

    public function markSent(?string $response = null): void
    {
        $this->forceFill([
            'status'      => self::SENT,
            'sent_at'     => now(),
            'reserved_at' => null,
            'response'    => $response,
            'last_error'  => null,
        ])->save();
    }

    /**
     * Record a refusal and decide whether it is worth another go.
     *
     * Anything still under the attempt limit goes back to pending with a
     * widening delay; anything past it stays failed until a person retries it
     * by hand, because the fault is not going to fix itself.
     */
    public function markFailed(string $error): void
    {
        $exhausted = $this->attempts >= $this->max_attempts;
        $wait      = self::BACKOFF[min($this->attempts, count(self::BACKOFF)) - 1] ?? end(self::BACKOFF);

        $this->forceFill([
            'status'       => $exhausted ? self::FAILED : self::PENDING,
            'reserved_at'  => null,
            'available_at' => $exhausted ? $this->available_at : now()->addMinutes($wait),
            'last_error'   => mb_substr($error, 0, 2000),
        ])->save();
    }

    /** Put a failed email back in the queue, counters reset. */
    public function retry(): void
    {
        $this->forceFill([
            'status'       => self::PENDING,
            'attempts'     => 0,
            'available_at' => null,
            'reserved_at'  => null,
            'last_error'   => null,
        ])->save();
    }

    /* ── Reading ────────────────────────────────────────────────────── */

    /** True when a worker took this and never came back — it can be re-claimed. */
    public function isAbandoned(int $minutes = 10): bool
    {
        return $this->status === self::SENDING
            && $this->reserved_at instanceof Carbon
            && $this->reserved_at->lt(now()->subMinutes($minutes));
    }

    public function recipient(): string
    {
        return $this->to_name
            ? "{$this->to_name} <{$this->to_address}>"
            : $this->to_address;
    }
}
