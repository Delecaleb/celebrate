<?php

/*
|--------------------------------------------------------------------------
| Celebration videos
|--------------------------------------------------------------------------
|
| One video per celebration. Whatever is uploaded is converted to a single
| dependable format — MP4, H.264 video, AAC audio — small enough to play on a
| phone on a slow connection. The numbers below are that format.
|
| FFmpeg does the converting. Where it is not on the PATH (most shared hosts),
| point FFMPEG_PATH and FFPROBE_PATH at the binaries. Where it is not available
| at all, uploads that are already a small MP4 are published as they are and
| anything else is refused with an explanation — see VideoProcessor.
|
*/

return [

    'ffmpeg'  => env('FFMPEG_PATH', 'ffmpeg'),
    'ffprobe' => env('FFPROBE_PATH', 'ffprobe'),

    // What may be uploaded. PHP's own upload_max_filesize and post_max_size
    // must be at least this, or the upload never reaches Laravel.
    'max_upload_mb' => (int) env('VIDEO_MAX_UPLOAD_MB', 50),
    'mimetypes'     => ['video/mp4', 'video/quicktime', 'video/webm'],

    // The limit that matters: how large the finished file may be. A video of
    // any length is accepted and squeezed to fit — first at full quality, then
    // at whatever bitrate and size the length allows. Only one too long to fit
    // even small and plain is refused.
    'max_output_mb' => (int) env('VIDEO_MAX_OUTPUT_MB', 20),

    // 0 keeps the whole video. Set a number to trim longer uploads to it.
    'max_seconds' => (int) env('VIDEO_MAX_SECONDS', 0),

    // When a video has to be squeezed, the sizes tried in turn (shorter side,
    // in pixels) and the least video bitrate, in kbps, worth showing at each.
    // Below the last one the video is refused as too long.
    'size_ladder' => [720 => 700, 540 => 420, 360 => 220],

    // The shorter side, so a portrait phone video becomes 720x1280 and a
    // landscape one 1280x720. Smaller videos are never scaled up.
    'max_short_side' => 720,
    'max_fps'        => 30,

    // 18 is near-lossless and large; 28 is small and visibly compressed.
    'crf'           => (int) env('VIDEO_CRF', 23),
    'preset'        => env('VIDEO_PRESET', 'medium'),
    'audio_bitrate' => '128k',

    // Seconds FFmpeg may run on one pass before it is stopped. Long videos
    // need longer.
    'timeout' => (int) env('VIDEO_TIMEOUT', 900),

    // After this many failed runs a video is marked failed, not retried forever.
    'max_attempts' => 3,

];
