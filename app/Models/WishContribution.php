<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WishContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'wish_id',
        'celebration_id',
        'contributor_user_id',
        'contributor_name',
        'contributor_email',
        'amount',
        'currency',
        'conversion_rate',
        'original_amount',
        'contribution_type',
        'message',
        'payment_reference',
        'payment_status',
        'is_anonymous',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'conversion_rate' => 'decimal:6',
        'original_amount' => 'decimal:2',
        'is_anonymous'    => 'boolean',
    ];

    public function wish()
    {
        return $this->belongsTo(Wish::class);
    }

    public function celebration()
    {
        return $this->belongsTo(Celebration::class);
    }

    public function contributor()
    {
        return $this->belongsTo(User::class, 'contributor_user_id');
    }
}
