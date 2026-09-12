<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Front controller — project root
|--------------------------------------------------------------------------
|
| Used where the web root is the project root rather than public/. The static
| files have not moved: they are still under public/, and .htaccess serves
| anything that exists there, so every asset() URL keeps working unchanged.
|
| That arrangement puts the whole codebase inside the web root, so .htaccess
| also has to refuse .env, storage/, vendor/ and the rest by hand. Read the
| deny block at the top of it before changing anything there.
|
*/

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
// (storage/ sits beside this file now, not one level up.)
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
