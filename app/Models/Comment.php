<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'celebration_id',
        'user_id',
        'guest_name',
        'guest_email',
        'message',
        'media_type',
        'media_url',
        'is_pinned',
        'is_approved',
        'like_count',
        'reply_count',
        // The visitor who left it, so a guest's wish can be matched to the
        // account they make later. See App\Support\Visitors.
        'visitor_id',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_approved' => 'boolean',
        'like_count' => 'integer',
        'reply_count' => 'integer',
    ];

    public function celebration()
    {
        return $this->belongsTo(Celebration::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reactions()
    {
        return $this->morphMany(Reaction::class, 'reactionable');
    }

    public function wallReplies()
    {
        return $this->hasMany(WallReply::class);
    }
}
