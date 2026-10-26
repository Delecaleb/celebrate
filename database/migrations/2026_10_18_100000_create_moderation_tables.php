<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Keeping the wall safe: people report what should not be there, block people
| they do not want to hear from, and staff review what was reported.
|
| Wall posts already have is_approved — until now nothing ever set it false.
| It becomes the "hidden" switch (a Comment global scope keeps hidden posts
| out of every page). Replies get the same switch here.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            // Who reported: an account, or a guest known only by device.
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_device', 120)->nullable();
            // What: a wall post, a reply, a celebration page or a person.
            $table->string('reportable_type', 32);
            $table->unsignedBigInteger('reportable_id');
            $table->string('reason', 32);
            $table->text('details')->nullable();
            // open → actioned (content hidden or removed) or dismissed.
            $table->string('status', 16)->default('open');
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['reportable_type', 'reportable_id']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('user_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['blocker_id', 'blocked_id']);
            $table->index('blocked_id');
        });

        Schema::table('wall_replies', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('wall_replies', function (Blueprint $table) {
            $table->dropColumn('is_hidden');
        });
        Schema::dropIfExists('user_blocks');
        Schema::dropIfExists('reports');
    }
};
