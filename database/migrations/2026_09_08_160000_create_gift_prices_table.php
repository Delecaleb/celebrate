<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A price per currency, for the gifts that need one.
 *
 * Converting one USD figure at the live rate is arithmetically right and
 * commercially wrong: it prices a gift at ₦651.37 when the number a Nigerian
 * guest expects to see is ₦800. This lets an admin set the figure that actually
 * belongs in each market.
 *
 * A row per currency rather than a column per currency, so adding a market is a
 * config edit — the admin form reads config('currency.currencies') and grows an
 * input on its own. No migration, no deploy.
 *
 * Where a currency has no row, the base price is converted as before. That is
 * the default an admin sets on the gift itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('platform_gift_id')
                ->constrained('platform_available_gifts')
                ->cascadeOnDelete();

            $table->char('currency', 3);
            $table->decimal('amount', 14, 2);

            $table->timestamps();

            // One price per gift per currency — anything else is ambiguous.
            $table->unique(['platform_gift_id', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gift_prices');
    }
};
