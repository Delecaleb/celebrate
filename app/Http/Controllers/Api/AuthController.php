<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

/**
 * Token auth for the mobile client.
 *
 * Mirrors the Breeze controllers in Auth/, but issues a Sanctum personal access
 * token instead of logging into the session. The web routes are untouched.
 */
class AuthController extends Controller
{
    /**
     * Same rules as RegisteredUserController: one `name` field split into
     * first/last, so an account made in the app is identical to a web one.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password'     => ['required', 'confirmed', Rules\Password::defaults()],
            'device_name'  => ['nullable', 'string', 'max:120'],
            // Optional here, for an app build that has no field for it yet.
            'phone'        => \App\Support\PhoneNumbers::rules(required: false),
        ]);

        $fullname = explode(' ', $data['name'], 2);

        $user = User::create([
            'uuid'       => str()->uuid(),
            'first_name' => $fullname[0],
            'last_name'  => $fullname[1] ?? 'User',
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'password'   => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        return response()->json([
            'token' => $this->issueToken($user, $request),
            'user'  => new UserResource($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            // Same shape as a validation error so the client renders it under
            // the email field exactly as the web form does.
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return response()->json([
            'token' => $this->issueToken($user, $request),
            'user'  => new UserResource($user),
        ]);
    }

    /**
     * Revoke only the token that made this call, so signing out on the phone
     * does not sign the same account out on another device.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user());
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name'  => ['sometimes', 'string', 'max:100'],
            'username'   => ['sometimes', 'nullable', 'string', 'max:60', 'unique:users,username,'.$user->id],
            'email'      => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone'      => ['sometimes', 'nullable', 'string', 'max:30'],
            'bio'        => ['sometimes', 'nullable', 'string', 'max:500'],
            'country'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'state'      => ['sometimes', 'nullable', 'string', 'max:100'],
            'city'       => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $user->fill($data);

        // Match ProfileController: changing your email un-verifies it.
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return new UserResource($user->fresh());
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        $user = $request->user();
        $user->update([
            'profile_photo' => $request->file('photo')->store('avatars', 'public'),
        ]);

        return new UserResource($user->fresh());
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Password updated.']);
    }

    /**
     * Send the reset link. Always reports success so the endpoint cannot be used
     * to discover which email addresses have accounts.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'If that email has an account, a reset link is on its way.',
        ]);
    }

    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent.']);
    }

    /**
     * Delete the account. Requires the password, as the web form does.
     */
    public function destroy(Request $request)
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted.']);
    }

    /**
     * One token per device, replacing any earlier token from the same device so
     * reinstalling the app does not leave dead tokens behind.
     */
    private function issueToken(User $user, Request $request): string
    {
        $device = $request->input('device_name') ?: 'mobile';

        $user->tokens()->where('name', $device)->delete();

        return $user->createToken($device)->plainTextToken;
    }
}
