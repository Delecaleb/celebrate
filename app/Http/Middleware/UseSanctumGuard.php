<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Make 'sanctum' the default guard for the whole API group.
 *
 * Without this, $request->user() on the API resolves against the *web* (session)
 * guard and is therefore always null — which would silently break every route
 * that is meant to work either way: viewing a celebration page, posting to the
 * wall as a guest, paying for a gift by card. A signed-in user would be treated
 * as a stranger and never see the Settings tab.
 *
 * It also makes the 'current_password' validation rule check the bearer-token
 * user rather than an empty session, which is what the change-password and
 * delete-account endpoints depend on.
 *
 * Routes that genuinely require a token still say so with auth:sanctum; this
 * only changes which guard answers when nobody asked for a specific one.
 */
class UseSanctumGuard
{
    public function handle(Request $request, Closure $next)
    {
        Auth::shouldUse('sanctum');

        return $next($request);
    }
}
