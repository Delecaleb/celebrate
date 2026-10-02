<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The celebration's video.
 *
 * `intro_video` has been on the table since the start, unused. It now holds the
 * finished file — one MP4 per celebration, converted to a size a phone on a
 * slow network can play. These columns carry everything around it: the upload
 * waiting to be converted, the still shown before it plays, and where the
 * conversion has got to, so the owner is told "getting it ready" or what went
 * wrong rather than left guessing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('celebrations', function (Blueprint $table) {
            // A JPG frame from the video: what the page shows until someone
            // presses play, so the video itself is not downloaded on arrival.
            $table->string('intro_video_poster')->nullable()->after('intro_video');

            // processing → ready | failed. Null when there is no video.
            $table->string('intro_video_status', 12)->nullable()->after('intro_video_poster');

            // The upload as it arrived, on the private disk, until it has been
            // converted — then deleted.
            $table->string('intro_video_source')->nullable()->after('intro_video_status');

            $table->unsignedSmallInteger('intro_video_seconds')->nullable()->after('intro_video_source');
            $table->unsignedTinyInteger('intro_video_attempts')->default(0)->after('intro_video_seconds');
            // In words the owner can act on.
            $table->string('intro_video_error')->nullable()->after('intro_video_attempts');

            // The converter's query: what is waiting.
            $table->index('intro_video_status');
        });
    }

    public function down(): void
    {
        Schema::table('celebrations', function (Blueprint $table) {
            $table->dropIndex(['intro_video_status']);
            $table->dropColumn([
                'intro_video_poster',
                'intro_video_status',
                'intro_video_source',
                'intro_video_seconds',
                'intro_video_attempts',
                'intro_video_error',
            ]);
        });
    }
};
