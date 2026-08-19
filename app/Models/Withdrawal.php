<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'bank_account_id',
        // As on WalletTransaction: the column exists and WithdrawalController
        // passes it, but it was not fillable, so every payout lost the record of
        // which wallet it came out of.
        'wallet_type',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'amount',
        'currency',
        'original_amount',
        'original_currency',
        'status',
        'reference',
        'note',
        'processed_at',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'original_amount' => 'decimal:2',
        'processed_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class)->withDefault([
            'bank_name'      => 'Removed account',
            'account_number' => '—',
            'account_name'   => '—',
        ]);
    }

    public function walletTransaction(): MorphOne
    {
        return $this->morphOne(WalletTransaction::class, 'transactionable');
    }
}
