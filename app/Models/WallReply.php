<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A reply to a post on the wishes wall (a Comment). */
class WallReply extends Model
{
    protected $fillable = [
        'comment_id',
        'user_id',
        'guest_name',
        'message',
        'is_hidden',
    ];

    protected $casts = [
        'is_hidden' => 'boolean',
    ];

    /** Hidden replies are left out everywhere but staff screens, as with Comment. */
    protected static function booted(): void
    {
        static::addGlobalScope('visible', fn ($query) => $query->where('wall_replies.is_hidden', false));
    }

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
