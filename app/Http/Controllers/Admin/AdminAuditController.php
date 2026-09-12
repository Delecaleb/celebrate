<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;

/**
 * What staff have been doing — super admins only.
 *
 * Every admin who opens a customer account, approves a payout, re-checks a
 * payment or changes another admin's access lands here. The customer never
 * sees it; the person who runs the platform always can.
 */
class AdminAuditController extends Controller
{
    public function index(Request $request)
    {
        $action  = (string) $request->query('action', '');
        $adminId = (string) $request->query('admin', '');
        $search  = trim((string) $request->query('q', ''));

        $entries = AdminAuditLog::query()
            ->with('admin')
            ->when($action !== '', fn ($q) => $q->where('action', $action))
            ->when($adminId !== '', fn ($q) => $q->where('admin_id', $adminId))
            ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('description', 'like', "%{$search}%")
                      ->orWhere('admin_email', 'like', "%{$search}%")
                      ->orWhere('subject_label', 'like', "%{$search}%")
                      ->orWhere('ip_address', 'like', "%{$search}%");
            }))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.audit', [
            'entries' => $entries,
            'action'  => $action,
            'adminId' => $adminId,
            'search'  => $search,
            'actions' => AdminAuditLog::ACTIONS,
            'admins'  => Admin::orderBy('name')->get(['id', 'name', 'email']),
            'summary' => [
                'total'         => AdminAuditLog::count(),
                'impersonation' => AdminAuditLog::where('action', 'admin.impersonation.start')->count(),
                'last24h'       => AdminAuditLog::where('created_at', '>=', now()->subDay())->count(),
            ],
        ]);
    }
}
