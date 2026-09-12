<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for the admin panel.
 *
 * Checks the `admin` guard, not the customer one — being signed in as a
 * customer, whatever flags that account carries, gets you nowhere here.
 */
class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin) {
            return redirect()->route('admin.login')
                ->with('error', 'Please sign in to access the admin panel.');
        }

        // Suspending an account has to take effect on the next request, not
        // whenever their session happens to expire.
        if (! $admin->isActive()) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'That admin account has been suspended.']);
        }

        return $next($request);
    }
}
