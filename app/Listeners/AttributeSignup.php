<?php

namespace App\Listeners;

use App\Support\Visitors;
use Illuminate\Auth\Events\Registered;

/**
 * A new account is tied to the visitor who made it.
 *
 * Discovered from app/Listeners by its handle(Registered) signature, like
 * SendWelcomeEmail, so every way of signing up — the register page, creating
 * a celebration, the app — goes through it without each one remembering to.
 * The request that fired the event is still in hand, and its cookie or device
 * header is the whole story.
 */
class AttributeSignup
{
    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (! $user instanceof \App\Models\User || ! app()->bound('request')) {
            return;
        }

        Visitors::attribute($user, Visitors::id(request()));
    }
}
