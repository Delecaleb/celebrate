<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the money paths while an admin is signed in as a customer.
 *
 * Impersonation exists so support can see what the customer sees. It must
 * never be a way to move somebody else's money, and "we trust our staff" is
 * not a control — this is.
 */
class NotWhileImpersonating
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('impersonator_admin_id')) {
            return $next($request);
        }

        $message = 'That action is disabled while viewing an account as its owner. Stop impersonating first.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'code' => 'impersonating'], 403);
        }

        return back()->with('error', $message);
    }
}
