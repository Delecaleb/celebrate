<?php

/*
|--------------------------------------------------------------------------
| Celebration videos
|--------------------------------------------------------------------------
|
| One video per celebration, published exactly as it arrives. Nothing is
| converted on the server: the app trims and shrinks the video on the phone
| before sending it, so what comes in is already the small MP4 the page plays.
| These are the limits that upload is held to.
|
*/

return [

    // The whole file. PHP's own upload_max_filesize and post_max_size must be
    // at least this, or the upload never reaches Laravel.
    'max_upload_mb' => (int) env('VIDEO_MAX_UPLOAD_MB', 20),

    // Only MP4: it is what every phone and browser plays without help, and the
    // app converts to it anyway.
    'mimetypes' => ['video/mp4'],

    // How long the video may run. The app opens a trimmer for anything longer.
    'max_seconds' => (int) env('VIDEO_MAX_SECONDS', 60),

    // Android cuts a video at the nearest keyframe before the chosen start, so
    // a trim to exactly 60s can come back a moment longer. That is not the
    // person's doing, so a small allowance is made for it.
    'grace_seconds' => 3,

];
