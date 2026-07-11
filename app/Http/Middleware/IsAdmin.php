<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('admin.login')
                ->with('error', 'Please sign in to access the admin panel.');
        }

        if (auth()->user()->account_type !== 'admin') {
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'You do not have admin access.']);
        }

        return $next($request);
    }
}
