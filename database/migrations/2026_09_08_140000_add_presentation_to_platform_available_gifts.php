<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gives the gift catalogue something to look like, and a state to be in.
 *
 * Gifts were drawn from gift_image_url — an uploaded file that this install has
 * none of, so every tile rendered a broken image. They now carry a Material
 * Design Icon name instead: the icon font is already loaded on every page, it
 * scales, it is themeable, and it needs no upload before a gift can go live.
 * An uploaded image still wins where one exists.
 *
 * `status` is the editorial state an admin sets. is_active stays in step with
 * it — see PlatformAvailableGift::booted — because the guest-facing queries
 * already read that column and there is no reason to break them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_available_gifts', function (Blueprint $table) {
            // e.g. 'mdi-cake-variant'. Nullable so an image-only gift is valid.
            $table->string('gift_icon', 60)->nullable()->after('gift_description');

            // Tints the tile. Falls back to the brand colour when empty.
            $table->string('accent_color', 20)->nullable()->after('gift_icon');

            $table->string('category', 40)->nullable()->after('accent_color');

            // Active: guests can send it. Pending: staff can see and edit it,
            // guests cannot. New gifts start pending so a half-finished one
            // never appears on a live celebration page.
            $table->enum('status', ['active', 'pending'])->default('pending')->after('is_active');

            $table->unsignedSmallInteger('sort_order')->default(0)->after('status');

            $table->index(['status', 'sort_order']);
        });

        // Whatever was already live stays live.
        DB::table('platform_available_gifts')
            ->where('is_active', true)
            ->update(['status' => 'active']);

        DB::table('platform_available_gifts')
            ->where('is_active', false)
            ->update(['status' => 'pending']);

        // gift_image_url was required, which forced an upload before a gift
        // could exist at all. An icon is enough now.
        Schema::table('platform_available_gifts', function (Blueprint $table) {
            $table->string('gift_image_url')->nullable()->change();
            $table->string('gift_link_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('platform_available_gifts', function (Blueprint $table) {
            $table->dropIndex(['status', 'sort_order']);
            $table->dropColumn(['gift_icon', 'accent_color', 'category', 'status', 'sort_order']);
        });
    }
};
