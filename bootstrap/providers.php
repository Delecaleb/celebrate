<?php

use App\Providers\AppServiceProvider;
use App\Providers\SettingsServiceProvider;

return [
    AppServiceProvider::class,
    // Overlays operator-managed settings onto the config. Registered after
    // AppServiceProvider so the container bindings it needs already exist.
    SettingsServiceProvider::class,
];
