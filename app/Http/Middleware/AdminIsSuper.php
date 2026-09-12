<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Super admins only.
 *
 * Used for the audit log, which records what every admin did — including the
 * one reading it. Making that a grantable permission would let a super admin
 * hand somebody the ability to watch their colleagues; it stays with whoever
 * runs the platform.
 */
class AdminIsSuper
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin?->is_super) {
            abort(403, 'Only a super admin can open that.');
        }

        return $next($request);
    }
}
