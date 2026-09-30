<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CelebrationGuestResource;
use App\Models\Celebration;
use App\Models\CelebrationGuest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Guests invited from the owner's phone contacts.
 *
 * Saving an invite does not send anything. Each guest is stored with the text
 * they are owed and sms_status = 'pending'; the SMS sender works through
 * CelebrationGuest::smsPending() and marks each one sent or failed.
 */
class CelebrationGuestController extends Controller
{
    public function store(Request $request, string $slug)
    {
        $celebration = Celebration::where('slug', $slug)->firstOrFail();
        abort_if($celebration->user_id !== $request->user()->id, 403, 'You do not own this celebration.');

        $data = $request->validate([
            'contacts'         => ['required', 'array', 'min:1', 'max:200'],
            'contacts.*.name'  => ['nullable', 'string', 'max:255'],
            'contacts.*.phone' => ['required', 'string', 'max:32'],
        ]);

        $host = $request->user();
        // Same display name UserResource gives the app, so the preview matches.
        $hostName = trim(($host->first_name ?? '').' '.($host->last_name ?? '')) ?: $host->username;

        // Numbers this celebration has already invited, so a second submit — or
        // one person listed twice in the address book — does not text them twice.
        $seen = $celebration->guests()
            ->whereNotNull('guest_phone')
            ->pluck('guest_phone')
            ->flip();

        $created = collect();
        $skipped = 0;

        DB::transaction(function () use ($data, $celebration, $host, $hostName, &$seen, $created, &$skipped) {
            foreach ($data['contacts'] as $contact) {
                $phone = CelebrationGuest::normalizePhone($contact['phone']);

                // Too short to be a real number once the formatting is gone.
                if (strlen(ltrim($phone, '+')) < 7 || $seen->has($phone)) {
                    $skipped++;
                    continue;
                }

                $seen->put($phone, true);
                $name = isset($contact['name']) ? trim($contact['name']) : null;

                $created->push(CelebrationGuest::create([
                    'celebration_id' => $celebration->id,
                    'guest_name'     => $name ?: null,
                    'guest_phone'    => $phone,
                    'invite_token'   => Str::random(40),
                    'invite_message' => CelebrationGuest::inviteMessage($celebration, $name, $hostName),
                    'sms_status'     => CelebrationGuest::SMS_PENDING,
                    'rsvp_status'    => 'pending',
                    'invited_by'     => $host->id,
                ]));
            }
        });

        $count = $created->count();

        return response()->json([
            'message' => $count === 0
                ? 'No new invitations: those numbers were already invited or are not valid.'
                : ($count === 1 ? '1 invitation is on its way.' : "{$count} invitations are on their way."),
            'invited' => $count,
            'skipped' => $skipped,
            'data'    => CelebrationGuestResource::collection($created),
        ], 201);
    }
}
