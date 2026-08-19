<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Celebrants remove registry items they do not want, but a wish may already
 * have contributions against it — the money and its history have to survive.
 * Soft delete keeps the row (and its contributions) while taking it off the page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wishes', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('wishes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
