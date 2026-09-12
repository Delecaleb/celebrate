<?php

namespace App\Observers;

use App\Models\User;
use App\Services\LocationModule\LocationService;

/**
 * Where a new account's currency comes from.
 *
 * Set once, at creation, from the country the signup came from — the column is
 * guarded from mass assignment, so no form can ever change it afterwards. It
 * decides which wallet the account has, so it is not something a customer gets
 * to pick.
 */
class UserObserver
{
    public function creating(User $user): void
    {
        $location = app(LocationService::class);
        $ip       = request()->ip();

        // The country and the currency come from the same answer, so support
        // can see why an account ended up where it did.
        $country = $location->countryOrFallback($ip);

        $user->currency = $location->getCurrencyForCountry($country);

        // Only when the signup did not supply one — a user who typed their
        // country knows better than an IP lookup does.
        if (! $user->country && $country) {
            $user->country = $country;
        }
    }
}
