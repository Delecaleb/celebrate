<?php

namespace App\Mail;

use App\Models\Celebration;

/**
 * How ready a celebration page is, in the terms its owner would use.
 *
 * Both of the "your day is nearly here" emails give the same advice, and advice
 * is only worth reading when it knows what has already been done — "add a cover
 * photo" is noise to someone who added four. So the state is worked out once,
 * here, and each email decides how to say it.
 */
final class CelebrationChecklist
{
    /**
     * @return array{
     *     isLive: bool, hasCover: bool, photoCount: int, registryCount: int,
     *     wishCount: int, giftCount: int, hasBank: bool, giftsOpen: bool,
     *     shareUrl: string, isReady: bool, toDo: array<int, string>
     * }
     */
    public static function for(Celebration $celebration): array
    {
        $owner = $celebration->user;

        $state = [
            'isLive'        => $celebration->status === 'published',
            'photoCount'    => count($celebration->cover_photos),
            'registryCount' => $celebration->wishes()->count(),
            'wishCount'     => $celebration->comments()->count(),
            'giftCount'     => $celebration->gifts()->where('payment_status', 'paid')->count(),
            'giftsOpen'     => (bool) $celebration->allow_gifts,
            'hasBank'       => $owner ? $owner->bankAccounts()->exists() : false,
            'shareUrl'      => route('celebrations.show', $celebration->slug),
        ];

        $state['hasCover'] = $state['photoCount'] > 0;

        // Only what is actually missing, in the order it matters: a page nobody
        // can reach is worse than one without a registry.
        $toDo = [];

        if (! $state['isLive']) {
            $toDo[] = 'Set the page live — in Settings, change Status from Draft to Live. Until then only you can see it.';
        }

        if (! $state['hasCover']) {
            $toDo[] = 'Add a cover photo — it is the first thing guests see, and pages with one get far more wishes.';
        }

        if ($state['registryCount'] === 0) {
            $toDo[] = 'Add a registry item or two, so people who ask "what do you need?" have an answer.';
        }

        if (! $state['hasBank']) {
            $toDo[] = 'Add your bank account in Wallet → Bank Account, so the money people send can reach you.';
        }

        $state['toDo']    = $toDo;
        $state['isReady'] = $toDo === [];

        return $state;
    }
}
