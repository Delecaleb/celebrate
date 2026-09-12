<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->safe()->only(['first_name', 'last_name', 'email']));

        // An unticked checkbox is absent from the request, so "missing" has to
        // mean off rather than "leave it alone".
        $user->email_notifications_enabled = $request->boolean('email_notifications_enabled');

        if ($request->boolean('remove_photo')) {
            $this->discardPhoto($user);
        } elseif ($request->hasFile('photo')) {
            $old = $user->profile_photo;
            $user->profile_photo = $request->file('photo')->store('avatars', 'public');

            // Only after the new one is safely stored.
            $this->deleteFile($old);
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    private function discardPhoto($user): void
    {
        $this->deleteFile($user->profile_photo);
        $user->profile_photo = null;
    }

    /**
     * Remove a stored avatar, tolerating one that is already gone — a missing
     * file must never stop somebody saving their name.
     */
    private function deleteFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        try {
            Storage::disk('public')->delete($path);
        } catch (\Throwable) {
            // Nothing to do: the row is what matters, not the orphan.
        }
    }
}
