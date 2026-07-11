<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'buyer_name',
        'buyer_email',
        'quantity',
        'total_amount',
        'payment_status',
        'qr_code',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function ticket()
    {
        return $this->belongsTo(EventTicket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
