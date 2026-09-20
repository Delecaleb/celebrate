<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the celebrant was last sent the report for this page.
 *
 * The report goes out after the day itself rather than on a weekly cycle, and
 * goes again only when something new has happened since — another gift, another
 * wish. That comparison needs a date to compare against, and this is it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('celebrations', function (Blueprint $table) {
            $table->timestamp('last_report_sent_at')->nullable()->after('view_count');
        });
    }

    public function down(): void
    {
        Schema::table('celebrations', function (Blueprint $table) {
            $table->dropColumn('last_report_sent_at');
        });
    }
};
