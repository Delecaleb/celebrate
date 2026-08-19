<?php

namespace App\Services\PaymentSystem;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    public function __construct(private CurrencyService $currency) {}

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    /**
     * Return the user's balance for the local or global (USD) wallet.
     */
    public function balance(User $user, string $walletType = 'local'): float
    {
        $type = (strtoupper($walletType) === 'USD' || $walletType === 'global') ? 'global' : 'local';
        if ($type === 'global') {
            return (float) $user->global_wallet_balance;
        }
        return (float) $user->wallet_balance;
    }

    public function hasSufficientBalance(User $user, float $amount, string $walletType = 'local'): bool
    {
        $type = (strtoupper($walletType) === 'USD' || $walletType === 'global') ? 'global' : 'local';
        if ($type === 'global') {
            return (float) $user->global_wallet_balance >= $amount;
        }
        return (float) $user->wallet_balance >= $amount;
    }

    // -------------------------------------------------------------------------
    // Write — always wrapped in a DB transaction
    // -------------------------------------------------------------------------

    public function debit(
        User    $user,
        float   $amount,
        string  $description,
        ?string $reference = null,
        ?Model  $source = null,
        ?float  $originalAmount = null,
        ?string $originalCurrency = null,
        string  $walletType = 'local'
    ): WalletTransaction {
        $type = (strtoupper($originalCurrency ?? '') === 'USD' || $walletType === 'global') ? 'global' : 'local';
        $currency = $type === 'global' ? 'USD' : ($user->currency ?? 'NGN');

        return DB::transaction(function () use (
            $user, $amount, $description, $reference,
            $source, $originalAmount, $originalCurrency, $type, $currency
        ) {
            if ($type === 'global') {
                $user->decrement('global_wallet_balance', $amount);
            } else {
                $user->decrement('wallet_balance', $amount);
            }
            $user->refresh();

            return WalletTransaction::create([
                'user_id'              => $user->id,
                'type'                 => 'debit',
                'wallet_type'          => $type,
                'amount'               => $amount,
                'currency'             => $currency,
                'original_amount'      => $originalAmount ?? $amount,
                'original_currency'    => $originalCurrency ?? $currency,
                'description'          => $description,
                'reference'            => $reference ?? (string) Str::uuid(),
                'status'               => 'completed',
                'transactionable_type' => $source ? get_class($source) : null,
                'transactionable_id'   => $source?->id,
            ]);
        });
    }

    public function credit(
        User    $user,
        float   $amount,
        string  $description,
        ?string $reference = null,
        ?Model  $source = null,
        ?float  $originalAmount = null,
        ?string $originalCurrency = null,
        string  $walletType = 'local'
    ): WalletTransaction {
        $type = (strtoupper($originalCurrency ?? '') === 'USD' || $walletType === 'global') ? 'global' : 'local';
        $currency = $type === 'global' ? 'USD' : ($user->currency ?? 'NGN');

        return DB::transaction(function () use (
            $user, $amount, $description, $reference,
            $source, $originalAmount, $originalCurrency, $type, $currency
        ) {
            if ($type === 'global') {
                $user->increment('global_wallet_balance', $amount);
            } else {
                $user->increment('wallet_balance', $amount);
            }
            $user->refresh();

            return WalletTransaction::create([
                'user_id'              => $user->id,
                'type'                 => 'credit',
                'wallet_type'          => $type,
                'amount'               => $amount,
                'currency'             => $currency,
                'original_amount'      => $originalAmount ?? $amount,
                'original_currency'    => $originalCurrency ?? $currency,
                'description'          => $description,
                'reference'            => $reference ?? (string) Str::uuid(),
                'status'               => 'completed',
                'transactionable_type' => $source ? get_class($source) : null,
                'transactionable_id'   => $source?->id,
            ]);
        });
    }
}
