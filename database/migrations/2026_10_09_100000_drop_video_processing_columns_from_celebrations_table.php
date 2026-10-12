<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Videos are no longer converted on the server.
 *
 * The app now trims and shrinks a video on the phone and sends the finished
 * MP4, which is published as it arrives. The columns that tracked a conversion
 * — the raw upload waiting its turn, how many times it had been tried, and why
 * it failed — have nothing left to record. A video that was still waiting when
 * this ran never got converted and is cleared, so its owner sees "Add a video"
 * rather than "getting ready" forever.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Anything mid-conversion or failed: there is no converter to finish it.
        DB::table('celebrations')
            ->whereIn('intro_video_status', ['processing', 'failed'])
            ->update([
                'intro_video'         => null,
                'intro_video_poster'  => null,
                'intro_video_status'  => null,
                'intro_video_seconds' => null,
            ]);

        // The raw uploads those rows pointed at.
        try {
            Storage::disk('local')->deleteDirectory('video-uploads');
            Storage::disk('local')->deleteDirectory('video-chunks');
        } catch (\Throwable) {
            // A missing folder is the same as an empty one.
        }

        Schema::table('celebrations', function (Blueprint $table) {
            $table->dropIndex(['intro_video_status']);
            $table->dropColumn(['intro_video_source', 'intro_video_attempts', 'intro_video_error']);
        });
    }

    public function down(): void
    {
        Schema::table('celebrations', function (Blueprint $table) {
            $table->string('intro_video_source')->nullable()->after('intro_video_status');
            $table->unsignedTinyInteger('intro_video_attempts')->default(0)->after('intro_video_seconds');
            $table->string('intro_video_error')->nullable()->after('intro_video_attempts');
            $table->index('intro_video_status');
        });
    }
};
