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
        Schema::table('celebrations', function (Blueprint $table) {
            $table->string('custom_bg', 7)->nullable()->after('template_id');
            $table->string('custom_text', 7)->nullable()->after('custom_bg');
        });
    }

    public function down(): void
    {
        Schema::table('celebrations', function (Blueprint $table) {
            $table->dropColumn(['custom_bg', 'custom_text']);
        });
    }
};
