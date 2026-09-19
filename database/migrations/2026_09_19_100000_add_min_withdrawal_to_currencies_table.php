<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The smallest payout the platform will process, per currency.
 *
 * It belongs on the currency rather than in one global setting: ₦5,000 and $5
 * are different amounts of "worth the transfer fee", and a single number in the
 * base currency converted at today's rate would drift.
 *
 * Zero means no minimum, which is what every existing currency starts at — so
 * this migration changes nothing until an admin sets a figure.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->decimal('min_withdrawal', 18, 2)->default(0)->after('fallback_rate');
        });
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn('min_withdrawal');
        });
    }
};
