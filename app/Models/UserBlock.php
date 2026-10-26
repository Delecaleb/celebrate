<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One person no longer wanting to see or hear from another. */
class UserBlock extends Model
{
    protected $fillable = [
        'blocker_id',
        'blocked_id',
    ];

    public function blocked()
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }
}
