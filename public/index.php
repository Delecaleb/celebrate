<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Front controller — public/
|--------------------------------------------------------------------------
|
| The app is also served from ../index.php, which is what runs in production
| where the web root is the project root. This copy stays because two things
| still expect it:
|
|   • php artisan serve, which hardcodes public/index.php
|   • any host where the web root is public/, which is the safer arrangement
|
| They are interchangeable. Neither holds logic of its own — both just boot
| bootstrap/app.php — so there is nothing to keep in sync beyond this notice.
|
*/

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
