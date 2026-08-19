<?php

namespace App\Listeners;

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
         * Mail::send() is synchronous, so anything wrong in the template or with
         * the mail transport propagated straight out of RegisteredUserController
         * and the API's AuthController::register. The account had already been
         * written by then, so the caller saw a 500 for a signup that had in fact
         * succeeded — and retrying then failed on "email already taken".
         */
        try {
            Mail::to($user->email)->send(new WelcomeMail($user));

            // Sent an hour later so it doesn't land at the same time.
            Mail::to($user->email)->later(now()->addHour(), new HowItWorksMail($user));
        } catch (\Throwable $e) {
            Log::error('Welcome email failed', [
                'user'  => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
