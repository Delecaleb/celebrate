<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'wish_id',
        'user_id',
        'guest_name',
        'message',
    ];

    public function wish()
    {
        return $this->belongsTo(Wish::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
