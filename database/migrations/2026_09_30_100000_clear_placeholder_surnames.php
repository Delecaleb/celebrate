<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * People who signed up with a single name were given a made-up surname —
 * "--" by the create-celebration sign-up, "User" by the register forms — and
 * it showed up everywhere their name did. Sign-up no longer does that; this
 * clears the ones already stored so those accounts read as one name.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereIn('last_name', ['--', 'User'])->update(['last_name' => '']);
    }

    public function down(): void
    {
        // The placeholders carried no information; nothing to restore.
    }
};
