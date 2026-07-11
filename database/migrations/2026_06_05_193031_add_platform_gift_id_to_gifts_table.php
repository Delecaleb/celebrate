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
            $table->foreignId('platform_gift_id')
                ->nullable()
                ->after('celebration_id')
                ->constrained('platform_available_gifts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gifts', function (Blueprint $table) {
            $table->dropForeign(['platform_gift_id']);
            $table->dropColumn('platform_gift_id');
        });
    }
};
