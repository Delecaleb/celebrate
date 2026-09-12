<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Signing in as a customer, to see exactly what they see.
 *
 * The admin guard stays authenticated throughout — impersonation adds a
 * customer session alongside it, which is what makes stopping it a matter of
 * dropping one guard rather than signing back in.
 *
 * Two rules hold it in place:
 *   • every start and stop is written to the admin audit log, with the admin
 *     named, because "who looked at that account" must be answerable — and it
 *     goes there rather than into the customer's own activity feed, where it
 *     would be both alarming to them and invisible to the person who needs it;
 *   • the money paths refuse to run while it is happening — see the
 *     not.impersonating middleware.
 */
class AdminImpersonationController extends Controller
{
    public function start(Request $request, User $user)
    {
        $admin = Auth::guard('admin')->user();

        if ($request->session()->has('impersonator_admin_id')) {
            return back()->with('error', 'You are already viewing an account. Stop that one first.');
        }

        // Nested impersonation, or impersonating while a customer session is
        // already open, makes the audit trail meaningless.
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        Auth::guard('web')->login($user);

        $request->session()->put('impersonator_admin_id', $admin->id);
        $request->session()->put('impersonated_user_id', $user->id);

        AdminAuditLog::record(
            'admin.impersonation.start',
            "Started viewing {$user->email}'s account",
            $user,
            $user->email,
        );

        Log::warning('Admin started impersonating a user', [
            'admin' => $admin->email,
            'user'  => $user->email,
            'ip'    => $request->ip(),
        ]);

        return redirect()->route('dashboard')
            ->with('success', "You are now viewing this account as {$user->first_name}. Nothing you do here can move money.");
    }

    /**
     * Hand the session back to the admin.
     *
     * Deliberately reachable from the customer side of the app — the banner
     * that offers it renders on the dashboard, not in the panel.
     */
    public function stop(Request $request)
    {
        $adminId = $request->session()->pull('impersonator_admin_id');
        $userId  = $request->session()->pull('impersonated_user_id');

        if (! $adminId) {
            return redirect()->route('admin.login');
        }

        $admin = \App\Models\Admin::find($adminId);
        $user  = User::find($userId);

        Auth::guard('web')->logout();

        if ($user && $admin) {
            AdminAuditLog::record(
                'admin.impersonation.stop',
                "Stopped viewing {$user->email}'s account",
                $user,
                $user->email,
            );

            Log::info('Admin stopped impersonating', ['admin' => $admin->email, 'user' => $user->email]);
        }

        // The admin guard was never dropped, so this lands straight back in
        // the panel.
        return redirect()->route('admin.users')
            ->with('success', $user ? "You are no longer viewing {$user->email}." : 'Impersonation ended.');
    }
}
