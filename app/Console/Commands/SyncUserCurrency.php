<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LocationModule\LocationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Put existing accounts on the currency they should have had.
 *
 * Accounts created before detection had a fallback were all given the base
 * currency, whatever country they signed up from. This re-decides from the
 * country on the account, or from the configured fallback where there is none.
 *
 * It will not touch an account that has money or a transaction history:
 * changing the currency changes which wallet the account has, and doing that
 * under a balance is how money goes missing. Those are listed instead, for
 * someone to deal with deliberately.
 *
 *   php artisan users:sync-currency --dry-run
 *   php artisan users:sync-currency
 *   php artisan users:sync-currency --country=NG      # treat unknowns as this
 */
class SyncUserCurrency extends Command
{
    protected $signature = 'users:sync-currency
                            {--dry-run : Show what would change, change nothing}
                            {--country= : Country to assume where the account has none}
                            {--force : Include accounts that hold a balance (dangerous)}';

    protected $description = 'Re-derive the stored currency for existing accounts';

    public function handle(LocationService $location): int
    {
        $dryRun   = (bool) $this->option('dry-run');
        $assumed  = $this->option('country') ?: config('currency.fallback_country');
        $changed  = 0;
        $skipped  = [];

        $this->line('Assuming <info>' . ($assumed ?: 'nothing') . '</info> where an account has no country.');
        $this->newLine();

        foreach (User::cursor() as $user) {
            $country  = $user->country ?: $assumed;
            $currency = $location->getCurrencyForCountry($country);

            if ($currency === $user->currency) {
                continue;
            }

            // Money already sitting in a wallet, or a history of it moving.
            $holdsMoney = (float) $user->wallet_balance > 0
                || (float) $user->global_wallet_balance > 0
                || $user->walletTransactions()->exists();

            if ($holdsMoney && ! $this->option('force')) {
                $skipped[] = "{$user->email} ({$user->currency} → {$currency})";
                continue;
            }

            $this->line(sprintf(
                '%s  %s → %s%s',
                $dryRun ? 'would change' : 'changed',
                str_pad($user->email, 34),
                $currency,
                $country ? "  [{$country}]" : ''
            ));

            if (! $dryRun) {
                // currency is guarded, so it has to be forced.
                $user->forceFill([
                    'currency' => $currency,
                    'country'  => $user->country ?: $country,
                ])->save();

                Log::info('User currency re-derived', [
                    'user'     => $user->email,
                    'currency' => $currency,
                    'country'  => $country,
                ]);
            }

            $changed++;
        }

        $this->newLine();
        $this->info(($dryRun ? 'Would change ' : 'Changed ') . $changed . ' account' . ($changed === 1 ? '' : 's'));

        if ($skipped !== []) {
            $this->newLine();
            $this->warn('Left alone because they hold a balance or have transactions:');

            foreach ($skipped as $line) {
                $this->line("  {$line}");
            }

            $this->newLine();
            $this->line('Move the balance out first, or re-run with --force if you are certain.');
        }

        return self::SUCCESS;
    }
}
