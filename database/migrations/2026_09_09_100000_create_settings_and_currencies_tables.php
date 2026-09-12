<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Settings an operator can change without a deploy, and the currencies the
 * platform trades in.
 *
 * Gateway keys, SMTP credentials and the location token lived only in .env,
 * which meant every rotation was an SSH session and a config:cache. They now
 * live here, encrypted, and overlay the config at boot — .env stays as the
 * fallback, so nothing breaks before anything is set.
 *
 * Currencies get their own table rather than a settings blob because the rest
 * of the app reads them structurally: gift prices are keyed by code, the
 * wallet decides local-or-global from them, and signup maps a country to one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            // 'payments', 'mail', 'location' — what the admin page groups by.
            $table->string('group', 40);
            $table->string('key', 80);

            // Long enough for a private key; nullable so "unset" is a state
            // distinct from "set to empty", which means fall back to .env.
            $table->text('value')->nullable();

            // Secrets are encrypted with APP_KEY and never rendered back to a
            // browser in full.
            $table->boolean('is_encrypted')->default(false);

            $table->foreignId('updated_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->unique(['group', 'key']);
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();

            $table->char('code', 3)->unique();          // ISO 4217
            $table->string('name', 60);
            $table->string('symbol', 8);
            $table->unsignedTinyInteger('decimals')->default(2);

            // Inactive: kept for history — gift prices and wallet balances that
            // reference it stay readable — but not offered to anyone new.
            $table->boolean('is_active')->default(true);

            // Rate from the base currency, used when the live API is
            // unreachable. The base currency's own rate is always 1.
            $table->decimal('fallback_rate', 18, 6)->default(1);

            // Countries whose visitors work in this currency: ISO codes or
            // lowercase names, exactly what currency.country_map held.
            $table->json('countries')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        $this->seedFromConfig();
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('settings');
    }

    /**
     * Start from whatever config/currency.php already declares, so this
     * migration changes where the values live and nothing else.
     */
    private function seedFromConfig(): void
    {
        $base      = strtoupper((string) config('currency.base', 'USD'));
        $countries = config('currency.country_map', []);
        $rates     = config('currency.fallback_rates', []);
        $order     = 0;

        foreach (config('currency.currencies', []) as $code => $meta) {
            $code = strtoupper($code);

            DB::table('currencies')->insert([
                'code'          => $code,
                'name'          => $meta['name'] ?? $code,
                'symbol'        => $meta['symbol'] ?? $code,
                'decimals'      => $meta['decimals'] ?? 2,
                'is_active'     => true,
                'fallback_rate' => $code === $base ? 1 : ($rates[$code] ?? 1),
                'countries'     => json_encode(array_keys(array_filter(
                    $countries,
                    fn ($mapped) => strtoupper($mapped) === $code
                ))),
                'sort_order'    => $order += 10,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
};
