<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send celebration reminder emails daily at 8:00 AM to owners and guests
Schedule::command('mail:celebration-reminders')->dailyAt('08:00');

// Send countdown emails daily at 9:00 AM to celebration owners
Schedule::command('mail:celebration-countdowns')->dailyAt('09:00');

// Send weekly gifting reports every Monday at 10:00 AM
Schedule::command('mail:gifting-reports --period=weekly')->weeklyOn(1, '10:00');

// Send monthly gifting reports on the 1st of each month at 10:00 AM
Schedule::command('mail:gifting-reports --period=monthly')->monthlyOn(1, '10:00');
