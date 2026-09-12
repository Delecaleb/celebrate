<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The staff door.
 *
 * Authenticates against the `admin` guard — a customer account, whatever it
 * holds, cannot sign in here.
 */
class AdminLoginController extends Controller
{
    /** Attempts allowed per email+IP before a lockout. */
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 300;

    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $this->ensureIsNotRateLimited($request);

        if (! Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request), self::LOCKOUT_SECONDS);

            Log::warning('Failed admin sign-in', [
                'email' => $request->input('email'),
                'ip'    => $request->ip(),
            ]);

            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->onlyInput('email');
        }

        $admin = Auth::guard('admin')->user();

        if (! $admin->isActive()) {
            Auth::guard('admin')->logout();

            RateLimiter::hit($this->throttleKey($request), self::LOCKOUT_SECONDS);

            return back()->withErrors([
                'email' => 'That admin account has been suspended.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($this->throttleKey($request));

        $request->session()->regenerate();

        $admin->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        Log::info('Admin signed in', ['admin' => $admin->email, 'ip' => $request->ip()]);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        // Signing out of the panel also ends any customer session the admin
        // opened by impersonating — leaving one behind would be a live login
        // to somebody else's account on a shared machine.
        if ($request->session()->has('impersonator_admin_id')) {
            Auth::guard('web')->logout();
            $request->session()->forget(['impersonator_admin_id', 'impersonated_user_id']);
        }

        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * @throws ValidationException when the caller is locked out
     */
    private function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "Too many attempts. Try again in {$seconds} seconds.",
        ]);
    }

    private function throttleKey(Request $request): string
    {
        return 'admin-login|' . Str::lower((string) $request->input('email')) . '|' . $request->ip();
    }
}
