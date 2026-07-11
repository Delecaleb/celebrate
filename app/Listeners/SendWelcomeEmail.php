<?php

namespace App\Listeners;

use App\Mail\HowItWorksMail;
use App\Mail\WelcomeMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        Mail::to($user->email)->send(new WelcomeMail($user));

        // Send the how-it-works guide 1 hour later so it doesn't land at the same time
        Mail::to($user->email)->later(now()->addHour(), new HowItWorksMail($user));
    }
}
