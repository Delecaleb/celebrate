<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payout accounts are verified against Paystack, which identifies a bank by
 * code rather than name. Nullable because accounts saved before verification
 * existed only ever recorded the name — they get a code the next time they are
 * edited, which is also when they get verified.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('bank_code', 10)->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn('bank_code');
        });
    }
};
