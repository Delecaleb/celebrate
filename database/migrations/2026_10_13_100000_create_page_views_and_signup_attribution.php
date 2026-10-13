<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Who visits, and how each account found the site.
 *
 * Every page view on the website and every celebration opened in the app is a
 * row in page_views, tagged with a visitor id: a cookie the browser keeps for
 * a year, or the app's device id. Nobody is asked anything. When a visitor
 * later signs up, the id on their browser is looked up and the first
 * celebration page it ever opened becomes the account's "discovered via" — the
 * celebration, and so the person, that brought them. A message left as a guest
 * carries the same id, so "left a wish, came back, signed up" can be seen.
 *
 * No IP addresses are kept. The session id is stored hashed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            // The cookie (a UUID) or the app's device id.
            $table->string('visitor_id', 64);
            // Hashed. One visit = one session; a visitor who comes back after
            // the session has ended counts as a second visit.
            $table->string('session_id', 40);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // Kept when the celebration is deleted, so totals do not fall.
            $table->foreignId('celebration_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path', 255);
            // Only when it is another site: where the visitor came from.
            $table->string('referrer', 512)->nullable();
            // 'web' or 'app'.
            $table->string('source', 8);
            $table->timestamp('viewed_at');

            // The daily chart reads by day; the attribution lookup reads by visitor.
            $table->index(['viewed_at', 'visitor_id']);
            $table->index(['visitor_id', 'viewed_at']);
            $table->index('celebration_id');
        });

        Schema::table('users', function (Blueprint $table) {
            // The visitor id on the browser or device they signed up from.
            $table->string('visitor_id', 64)->nullable()->index()->after('last_seen_at');
            // The first celebration page that visitor ever opened: who brought them.
            $table->foreignId('discovered_via_celebration_id')->nullable()->after('visitor_id')
                ->constrained('celebrations')->nullOnDelete();
            // When that first page was opened — how long before signing up they looked.
            $table->timestamp('discovered_at')->nullable()->after('discovered_via_celebration_id');
            // The outside site their very first view came from, if any.
            $table->string('first_referrer', 255)->nullable()->after('discovered_at');
        });

        Schema::table('comments', function (Blueprint $table) {
            // So a guest's message can be matched to the account they make later.
            $table->string('visitor_id', 64)->nullable()->index()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex(['visitor_id']);
            $table->dropColumn('visitor_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discovered_via_celebration_id');
            $table->dropIndex(['visitor_id']);
            $table->dropColumn(['visitor_id', 'discovered_at', 'first_referrer']);
        });

        Schema::dropIfExists('page_views');
    }
};
