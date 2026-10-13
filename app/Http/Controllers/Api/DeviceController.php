<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

/**
 * The app registers its Expo push token here after sign-in and removes it on
 * sign-out, so a shared phone never buzzes for the previous account.
 */
class DeviceController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'token'    => ['required', 'string', 'max:255', 'regex:/^(Exponent|Expo)PushToken\[.+\]$/'],
            'platform' => ['nullable', 'in:ios,android'],
        ]);

        // updateOrCreate on the token, not the user: if this phone was signed
        // in as someone else, it now belongs to whoever just signed in.
        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id'      => $request->user()->id,
                'platform'     => $data['platform'] ?? null,
                'last_used_at' => now(),
            ],
        );

        return response()->json(['registered' => true], 201);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->deviceTokens()->where('token', $data['token'])->delete();

        return response()->json(['registered' => false]);
    }
}
