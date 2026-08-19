<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        // The column has always existed and every writer passes it, but it was
        // missing here — so mass assignment dropped it and the funding callbacks
        // (which branch on $tx->wallet_type) credited the local wallet for USD
        // top-ups. It has to stay fillable.
        'wallet_type',
        'amount',
        'currency',
        'original_amount',
        'original_currency',
        'description',
        'reference',
        'status',
        'transactionable_type',
        'transactionable_id',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'original_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }
}
