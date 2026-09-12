<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * USD users don't have a local wallet.
 *
 * The dual-wallet split exists so that countries without USD checkout can hold
 * money in their own currency. Where USD checkout works, the "local" wallet was
 * a second USD balance sitting beside the global one: funding credited it,
 * spending drew on the global wallet, and a withdrawal checked one and debited
 * the other. This folds those balances back into the single wallet those users
 * actually have, at 1:1 — both sides were already USD, so no rate is involved.
 */
return new class extends Migration
{
    public function up(): void
    {
        $usdUserIds = [];

        DB::table('users')
            ->select('id', 'currency', 'country', 'wallet_balance')
            ->orderBy('id')
            ->chunkById(500, function ($users) use (&$usdUserIds) {
                foreach ($users as $user) {
                    if ($this->currencyFor($user) !== 'USD') {
                        continue;
                    }

                    $usdUserIds[] = $user->id;
                    $stranded     = (float) $user->wallet_balance;

                    if ($stranded == 0.0) {
                        continue;
                    }

                    DB::table('users')->where('id', $user->id)->update([
                        'global_wallet_balance' => DB::raw('global_wallet_balance + ' . $stranded),
                        'wallet_balance'        => 0,
                    ]);
                }
            });

        // Relabel their history so the ledger agrees with the balances.
        foreach (array_chunk($usdUserIds, 500) as $chunk) {
            DB::table('wallet_transactions')
                ->whereIn('user_id', $chunk)
                ->where('wallet_type', 'local')
                ->update(['wallet_type' => 'global']);

            DB::table('withdrawals')
                ->whereIn('user_id', $chunk)
                ->where('wallet_type', 'local')
                ->update(['wallet_type' => 'global']);
        }
    }

    public function down(): void
    {
        // Not reversible: once merged there is no record of which part of the
        // global balance came from the old local column.
    }

    private function currencyFor(object $user): string
    {
        if ($user->currency) {
            return strtoupper(trim($user->currency));
        }

        if ($user->country) {
            $key = strtolower(trim($user->country));

            return strtoupper(config("currency.country_map.{$key}", config('currency.base', 'USD')));
        }

        return strtoupper(config('currency.base', 'USD'));
    }
};
