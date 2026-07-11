<?php

namespace App\Observers;

use App\Models\User;
use App\Services\LocationModule\LocationService;

class UserObserver
{
    /**
     * Set the user's currency from their IP before the record is created.
     * This column is guarded from mass assignment and can never be updated via forms.
     */
    public function creating(User $user): void
    {
        $ip       = request()->ip();
        $location = app(LocationService::class);

        $user->currency = $location->getCurrencyFromIp($ip);
    }
}
