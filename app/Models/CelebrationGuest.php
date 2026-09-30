<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CelebrationGuest extends Model
{
    use HasFactory;

    /** sms_status values. Null means no SMS is owed. */
    public const SMS_PENDING = 'pending';
    public const SMS_SENT    = 'sent';
    public const SMS_FAILED  = 'failed';

    protected $fillable = [
        'celebration_id',
        'user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'invite_token',
        'invite_message',
        'sms_status',
        'sms_sent_at',
        'sms_error',
        'rsvp_status',
        'invited_by',
        'checked_in_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'sms_sent_at'   => 'datetime',
    ];

    public function celebration()
    {
        return $this->belongsTo(Celebration::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /** Invites still waiting for their SMS, oldest first. */
    public function scopeSmsPending($query)
    {
        return $query->where('sms_status', self::SMS_PENDING)->oldest();
    }

    /**
     * The invitation text.
     *
     * The app previews this before the owner submits, from its own copy in
     * celebrateMobile/src/lib/invite.ts — change the wording in both places.
     */
    public static function inviteMessage(Celebration $celebration, ?string $guestName, ?string $hostName): string
    {
        $first = trim(strtok(trim((string) $guestName), ' ') ?: '');
        $greeting = $first !== '' ? "Hi {$first}!" : 'Hi there!';
        $host = trim((string) $hostName) !== '' ? trim($hostName) : 'A friend';
        $link = route('celebrations.show', $celebration->slug);

        return "{$greeting} {$host} is inviting you to celebrate {$celebration->title} on CelebrateMi. "
            ."Send your wishes here: {$link}";
    }

    /**
     * A phone number as it will be stored and texted: digits, with a leading +
     * kept if there was one. Spaces, dashes, dots and brackets from the address
     * book are dropped.
     */
    public static function normalizePhone(string $phone): string
    {
        $phone = trim($phone);
        $digits = preg_replace('/\D+/', '', $phone);

        return (str_starts_with($phone, '+') ? '+' : '').$digits;
    }
}
