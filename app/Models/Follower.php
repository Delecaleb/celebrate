<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Follower extends Model
{
    use HasFactory;

    protected $fillable = [
        'follower_id',
        'followable_id',
        'followable_type',
    ];

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function followable(): MorphTo
    {
        return $this->morphTo();
    }
}
