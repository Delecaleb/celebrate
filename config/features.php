<?php

/**
 * Parts of the product an operator can switch on and off.
 *
 * Each can be overridden from Admin → Settings → Features, which takes
 * precedence over .env (see SettingsRepository).
 */

return [

    // Photo frames a celebrant can put around their page. Off by default: the
    // frame library ships empty, and an empty picker is worse than none.
    'frames' => env('FEATURE_FRAMES', false),

];
