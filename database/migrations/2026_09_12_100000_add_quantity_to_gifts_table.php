<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How many of a gift was sent in one go.
 *
 * Until now three cupcake boxes meant three rows, which worked but read badly:
 * the wall counted rows rather than items, and a giver had to pay three times.
 * Existing rows are one each, which is exactly what the default says.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gifts', function (Blueprint $table) {
            $table->unsignedSmallInteger('quantity')->default(1)->after('platform_gift_id');
        });
    }

    public function down(): void
    {
        Schema::table('gifts', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
