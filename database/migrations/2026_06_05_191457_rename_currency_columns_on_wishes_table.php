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
            $table->renameColumn('amount_usd', 'amount_base');
            $table->renameColumn('amount_ngn', 'amount_converted');
            $table->string('base_currency', 10)->nullable()->after('amount_base');
            $table->string('converted_currency', 10)->nullable()->after('amount_converted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wishes', function (Blueprint $table) {
            $table->renameColumn('amount_base', 'amount_usd');
            $table->renameColumn('amount_converted', 'amount_ngn');
            $table->dropColumn(['base_currency', 'converted_currency']);
        });
    }
};
