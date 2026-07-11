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
     * Return the user's balance converted to $currency.
     * The ledger is always stored in the base currency (USD).
     */
    public function balance(User $user, ?string $currency = null): float
    {
        $base = (float) $user->wallet_balance;

        if ($currency === null || $currency === config('currency.base')) {
            return $base;
        }

        return $this->currency->convert($base, config('currency.base'), $currency);
    }

    public function hasSufficientBalance(User $user, float $amountInBase): bool
    {
        return (float) $user->wallet_balance >= $amountInBase;
    }

    // -------------------------------------------------------------------------
    // Write — always wrapped in a DB transaction
    // -------------------------------------------------------------------------

    public function debit(
        User    $user,
        float   $amountBase,
        string  $description,
        ?string $reference = null,
        ?Model  $source = null,
        ?float  $originalAmount = null,
        ?string $originalCurrency = null,
    ): WalletTransaction {
        return DB::transaction(function () use (
            $user, $amountBase, $description, $reference,
            $source, $originalAmount, $originalCurrency
        ) {
            $user->decrement('wallet_balance', $amountBase);
            $user->refresh();

            return WalletTransaction::create([
                'user_id'              => $user->id,
                'type'                 => 'debit',
                'amount'               => $amountBase,
                'currency'             => config('currency.base'),
                'original_amount'      => $originalAmount,
                'original_currency'    => $originalCurrency,
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
        float   $amountBase,
        string  $description,
        ?string $reference = null,
        ?Model  $source = null,
        ?float  $originalAmount = null,
        ?string $originalCurrency = null,
    ): WalletTransaction {
        return DB::transaction(function () use (
            $user, $amountBase, $description, $reference,
            $source, $originalAmount, $originalCurrency
        ) {
            $user->increment('wallet_balance', $amountBase);
            $user->refresh();

            return WalletTransaction::create([
                'user_id'              => $user->id,
                'type'                 => 'credit',
                'amount'               => $amountBase,
                'currency'             => config('currency.base'),
                'original_amount'      => $originalAmount,
                'original_currency'    => $originalCurrency,
                'description'          => $description,
                'reference'            => $reference ?? (string) Str::uuid(),
                'status'               => 'completed',
                'transactionable_type' => $source ? get_class($source) : null,
                'transactionable_id'   => $source?->id,
            ]);
        });
    }
}
