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

// Two days out, and the morning of: how to edit the page, how to share it, and
// a setup check. Early, so the day-of email is there before the day starts.
Schedule::command('mail:celebration-notices')->dailyAt('07:00');

/*
| The report on a finished celebration, at 11:00 the day after it ends. It
| goes again only when more arrives — people keep giving for days afterwards —
| and never again for a page nobody has touched since.
*/
Schedule::command('mail:event-reports')->dailyAt('11:00');

// Settle payments a callback or webhook never confirmed. Ten minutes is short
// enough that nobody waits long, and long enough that a slow gateway redirect
// has already had its chance.
/*
| The outbox. Everything the site sends is written to email_queues and posted
| from here, so mail rides on the scheduler the app already needs rather than
| on a second daemon somebody has to remember to start.
*/
Schedule::command('emails:send')->everyMinute()->withoutOverlapping();

Schedule::command('payments:reconcile')->everyTenMinutes()->withoutOverlapping();
