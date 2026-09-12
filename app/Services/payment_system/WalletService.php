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
    // Which wallets a user actually has
    // -------------------------------------------------------------------------

    /**
     * The local wallet only exists where USD checkout isn't available.
     *
     * Somebody in a USD country has one wallet — the global USD one. Giving
     * them a second, also-USD "local" wallet splits their money across two
     * balances that no rate separates, so every read and write below collapses
     * to global for them.
     */
    public function hasLocalWallet(?User $user): bool
    {
        return $user !== null && strtoupper($this->currency->forUser($user)) !== 'USD';
    }

    /**
     * Collapse a requested wallet type — 'local', 'global', or a currency code
     * such as 'NGN'/'USD' — to the wallet this user really has.
     *
     * $currency is the currency the money is moving in: USD money always lands
     * in the global wallet, whatever the caller asked for.
     */
    public function resolveWalletType(?User $user, ?string $requested = 'local', ?string $currency = null): string
    {
        if (! $this->hasLocalWallet($user)) {
            return 'global';
        }

        $requested = strtolower(trim((string) $requested));

        return ($requested === 'global' || $requested === 'usd' || strtoupper((string) $currency) === 'USD')
            ? 'global'
            : 'local';
    }

    /**
     * The balance column backing a wallet type, for callers that increment the
     * user row directly instead of going through debit()/credit().
     */
    public function balanceColumn(?User $user, ?string $walletType = 'local'): string
    {
        return $this->resolveWalletType($user, $walletType) === 'global'
            ? 'global_wallet_balance'
            : 'wallet_balance';
    }

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    /**
     * Return the user's balance for the local or global (USD) wallet.
     */
    public function balance(User $user, string $walletType = 'local'): float
    {
        return $this->resolveWalletType($user, $walletType) === 'global'
            ? (float) $user->global_wallet_balance
            : (float) $user->wallet_balance;
    }

    public function hasSufficientBalance(User $user, float $amount, string $walletType = 'local'): bool
    {
        return $this->balance($user, $walletType) >= $amount;
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
        $type = $this->resolveWalletType($user, $walletType, $originalCurrency);
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
        $type = $this->resolveWalletType($user, $walletType, $originalCurrency);
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
