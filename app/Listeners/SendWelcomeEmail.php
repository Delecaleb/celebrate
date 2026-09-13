<?php

namespace App\Listeners;

use App\Support\Outbox;
use App\Mail\HowItWorksMail;
use App\Mail\WelcomeMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        /*
         * Never let a courtesy email fail the registration that triggered it.
         *
         * This used to send synchronously, so anything wrong in the template or
         * with the transport propagated straight out of RegisteredUserController
         * and the API's AuthController::register. The account had already been
         * written by then, so the caller saw a 500 for a signup that had in fact
         * succeeded — and retrying then failed on "email already taken".
         *
         * Outbox writes a row and returns; it renders the template here, so a
         * broken one is still caught immediately, but it never throws.
         */
        Outbox::queue(
            new WelcomeMail($user),
            $user->email,
            'user.welcome',
            ['user_id' => $user->id],
            $user->first_name,
        );

        // Sent an hour later so it doesn't land at the same time.
        Outbox::queue(
            new HowItWorksMail($user),
            $user->email,
            'user.how-it-works',
            ['user_id' => $user->id],
            $user->first_name,
            now()->addHour(),
        );
    }
}
