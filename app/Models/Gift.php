<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    use HasFactory;

    protected $fillable = [
        'celebration_id',
        'platform_gift_id',
        'sender_user_id',
        'sender_name',
        'sender_email',
        'amount',
        'currency',
        'guest_currency',
        'conversion_rate',
        'payment_method',
        'transaction_reference',
        'payment_status',
        'message',
        'is_anonymous',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'conversion_rate' => 'decimal:6',
        'is_anonymous'    => 'boolean',
    ];

    public function celebration()
    {
        return $this->belongsTo(Celebration::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function platformGift()
    {
        return $this->belongsTo(PlatformAvailableGift::class);
    }
}
