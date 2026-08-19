<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('global_wallet_balance', 14, 2)->default(0.00)->after('wallet_balance');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->string('wallet_type', 10)->default('local')->after('type');
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('wallet_type', 10)->default('local')->after('bank_account_id');
        });

        // Data migration:
        // Convert existing users' wallet_balance (which was in USD) to their local currency,
        // and initialize global_wallet_balance.
        // We will retrieve user currency. If user currency is not USD (e.g. NGN), we convert the balance
        // using the fallback/current rate so they have local currency in wallet_balance.
        $users = DB::table('users')->get();
        $fallbackRates = config('currency.fallback_rates', ['NGN' => 1620.00]);

        foreach ($users as $user) {
            $currency = $user->currency;
            if (!$currency) {
                // Determine currency from country if not set
                if ($user->country) {
                    $key = strtolower(trim($user->country));
                    $currency = config("currency.country_map.{$key}", 'USD');
                } else {
                    $currency = 'USD';
                }
            }

            $currency = strtoupper($currency);

            if ($currency !== 'USD' && $user->wallet_balance > 0) {
                $rate = $fallbackRates[$currency] ?? 1.0;
                $localBalance = round($user->wallet_balance * $rate, 2);

                DB::table('users')->where('id', $user->id)->update([
                    'currency' => $currency,
                    'wallet_balance' => $localBalance,
                ]);
            } else if ($user->wallet_balance > 0) {
                // If it is USD, ensure currency is set to USD
                DB::table('users')->where('id', $user->id)->update([
                    'currency' => 'USD',
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('global_wallet_balance');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropColumn('wallet_type');
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn('wallet_type');
        });
    }
};
