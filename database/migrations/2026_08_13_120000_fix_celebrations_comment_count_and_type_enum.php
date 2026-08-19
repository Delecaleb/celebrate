<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two fixes to the celebrations table, both of which broke creating a page.
 *
 * 1. `comment_count` had drifted to NOT NULL with no default, so any insert
 *    that left it out (every one — nothing sets a starting comment count)
 *    failed under MySQL strict mode with "Field 'comment_count' doesn't have a
 *    default value". The original migration always meant it to default to 0,
 *    like view_count and share_count next to it.
 *
 * 2. `baby_shower` was missing from the celebration_type enum even though all
 *    three create forms offer it, so picking Baby Shower could never be saved.
 */
return new class extends Migration
{
    private const TYPES = [
        'birthday', 'wedding', 'memorial', 'graduation',
        'anniversary', 'baby_shower', 'other',
    ];

    private const TYPES_BEFORE = [
        'birthday', 'wedding', 'memorial', 'graduation', 'anniversary', 'other',
    ];

    public function up(): void
    {
        Schema::table('celebrations', function (Blueprint $table) {
            $table->unsignedInteger('comment_count')->default(0)->change();
            $table->enum('celebration_type', self::TYPES)->default('birthday')->change();
        });
    }

    public function down(): void
    {
        // Only the enum is reverted. Restoring the missing default on
        // comment_count would just reintroduce the insert failure above.
        Schema::table('celebrations', function (Blueprint $table) {
            $table->enum('celebration_type', self::TYPES_BEFORE)->default('birthday')->change();
        });
    }
};
