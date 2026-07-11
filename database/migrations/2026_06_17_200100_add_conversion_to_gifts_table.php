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
        Schema::table('gifts', function (Blueprint $table) {
            $table->string('guest_currency', 3)->nullable()->after('amount');
            $table->decimal('conversion_rate', 12, 6)->nullable()->after('guest_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gifts', function (Blueprint $table) {
            $table->dropColumn(['guest_currency', 'conversion_rate']);
        });
    }
};
