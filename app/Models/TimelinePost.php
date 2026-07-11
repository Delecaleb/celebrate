<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimelinePost extends Model
{
    use HasFactory;

    protected $fillable = [
        'celebration_id',
        'user_id',
        'guest_name',
        'content',
        'media_url',
        'visibility',
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
