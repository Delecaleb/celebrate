<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permission gate for a single admin route.
 *
 *   ->middleware('admin.can:payments.view')
 *   ->middleware('admin.can:withdrawals.view,withdrawals.process')   // any of
 *
 * Runs after IsAdmin, so an admin is already guaranteed here. Super admins
 * pass everything.
 */
class AdminCan
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin || ! $admin->hasAnyPermission($permissions)) {
            abort(403, 'Your admin account does not have access to that.');
        }

        return $next($request);
    }
}
