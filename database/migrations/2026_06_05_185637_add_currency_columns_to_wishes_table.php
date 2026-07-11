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
        Schema::table('wishes', function (Blueprint $table) {
            $table->decimal('amount_usd', 14, 2)->nullable()->after('target_amount');
            $table->decimal('amount_ngn', 14, 2)->nullable()->after('amount_usd');
            $table->decimal('conversion_rate', 18, 6)->nullable()->after('amount_ngn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wishes', function (Blueprint $table) {
            $table->dropColumn(['amount_usd', 'amount_ngn', 'conversion_rate']);
        });
    }
};
