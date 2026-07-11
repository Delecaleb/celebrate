<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'celebration_id',
        'title',
        'description',
        'target_amount',
        'current_amount',
        'cover_image',
        'deadline',
        'status',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'deadline' => 'datetime',
    ];

    public function celebration()
    {
        return $this->belongsTo(Celebration::class);
    }
}
