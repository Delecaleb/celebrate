<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CelebrationGuest extends Model
{
    use HasFactory;

    protected $fillable = [
        'celebration_id',
        'user_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'invite_token',
        'rsvp_status',
        'invited_by',
        'checked_in_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
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
}
