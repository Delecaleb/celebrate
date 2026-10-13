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
    ];

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
