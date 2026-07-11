<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'celebration_id',
        'user_id',
        'guest_name',
        'file_type',
        'file_path',
        'thumbnail',
        'caption',
        'file_size',
        'mime_type',
        'visibility',
    ];

    protected $casts = [
        'file_size' => 'integer',
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
