<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wish_contributions', function (Blueprint $table) {
            // Currency the contributor paid in (e.g. NGN for a Nigerian visitor)
            $table->string('currency', 3)->nullable()->after('amount');
            // Rate used at time of contribution (base_currency → contributor_currency)
            $table->decimal('conversion_rate', 12, 6)->nullable()->after('currency');
            // Amount in the contributor's own currency (what they saw on screen)
            $table->decimal('original_amount', 15, 2)->nullable()->after('conversion_rate');
        });
    }

    public function down(): void
    {
        Schema::table('wish_contributions', function (Blueprint $table) {
            $table->dropColumn(['currency', 'conversion_rate', 'original_amount']);
        });
    }
};
