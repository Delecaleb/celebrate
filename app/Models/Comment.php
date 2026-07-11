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
}
