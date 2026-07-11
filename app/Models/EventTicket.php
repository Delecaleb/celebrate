<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'celebration_id',
        'ticket_name',
        'price',
        'quantity',
        'sold_count',
        'sales_start',
        'sales_end',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sales_start' => 'datetime',
        'sales_end' => 'datetime',
    ];

    public function celebration()
    {
        return $this->belongsTo(Celebration::class);
    }

    public function purchases()
    {
        return $this->hasMany(TicketPurchase::class, 'ticket_id');
    }
}
