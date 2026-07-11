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
            $table->foreignId('template_id')
                  ->nullable()
                  ->after('status')
                  ->constrained('celebration_templates')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('celebrations', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn('template_id');
        });
    }
};
