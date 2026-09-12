<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Staff accounts and what each of them can reach.
 *
 * Only a super admin gets in here — the route carries admins.manage, which
 * super admins hold implicitly and nobody else should be granted lightly.
 */
class AdminStaffController extends Controller
{
    public function index()
    {
        $admins = Admin::with(['permissions', 'creator'])
            ->orderByDesc('is_super')
            ->orderBy('name')
            ->get();

        return view('admin.staff.index', [
            'admins'      => $admins,
            'permissions' => Admin::permissionsByGroup(),
        ]);
    }

    public function create()
    {
        return view('admin.staff.form', [
            'admin'       => new Admin(['status' => 'active']),
            'permissions' => Admin::permissionsByGroup(),
            'granted'     => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $admin = Admin::create([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => $data['password'],
            'is_super'   => (bool) ($data['is_super'] ?? false),
            'status'     => $data['status'],
            'created_by' => Auth::guard('admin')->id(),
        ]);

        $admin->syncPermissions($data['permissions'] ?? [], Auth::guard('admin')->user());

        AdminAuditLog::record(
            'admin.staff.created',
            sprintf(
                'Added %s as %s',
                $admin->email,
                $admin->is_super ? 'a super admin' : ($admin->permissions->pluck('permission')->implode(', ') ?: 'sign-in only')
            ),
            $admin,
            $admin->email,
        );

        Log::info('Admin account created', [
            'by'    => Auth::guard('admin')->user()->email,
            'admin' => $admin->email,
            'super' => $admin->is_super,
        ]);

        return redirect()->route('admin.staff')
            ->with('success', "{$admin->name} can now sign in to the admin panel.");
    }

    public function edit(Admin $admin)
    {
        return view('admin.staff.form', [
            'admin'       => $admin,
            'permissions' => Admin::permissionsByGroup(),
            'granted'     => $admin->permissions->pluck('permission')->all(),
        ]);
    }

    public function update(Request $request, Admin $admin)
    {
        $data = $this->validated($request, $admin);

        // Never let the last super admin stop being one, or nobody can manage
        // staff again without a console.
        $removingLastSuper = $admin->is_super
            && ! ($data['is_super'] ?? false)
            && Admin::where('is_super', true)->where('status', 'active')->count() <= 1;

        if ($removingLastSuper) {
            return back()->withInput()
                ->with('error', 'This is the only super admin — promote someone else first.');
        }

        $admin->fill([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'is_super' => (bool) ($data['is_super'] ?? false),
            'status'   => $data['status'],
        ]);

        if (! empty($data['password'])) {
            $admin->password = $data['password'];
        }

        $admin->save();
        $admin->syncPermissions($data['permissions'] ?? [], Auth::guard('admin')->user());

        AdminAuditLog::record(
            'admin.staff.updated',
            sprintf(
                'Set %s to %s (%s)',
                $admin->email,
                $admin->is_super ? 'super admin' : ($admin->permissions->pluck('permission')->implode(', ') ?: 'sign-in only'),
                $admin->status
            ),
            $admin,
            $admin->email,
        );

        Log::info('Admin account updated', [
            'by'    => Auth::guard('admin')->user()->email,
            'admin' => $admin->email,
        ]);

        return redirect()->route('admin.staff')
            ->with('success', "{$admin->name}'s access has been updated.");
    }

    public function destroy(Admin $admin)
    {
        if ($admin->id === Auth::guard('admin')->id()) {
            return back()->with('error', 'You cannot remove your own admin account.');
        }

        if ($admin->is_super && Admin::where('is_super', true)->count() <= 1) {
            return back()->with('error', 'That is the only super admin.');
        }

        $email = $admin->email;

        // Recorded before the delete, so the subject id is still meaningful.
        AdminAuditLog::record('admin.staff.deleted', "Removed admin {$email}", $admin, $email);

        $admin->delete();

        Log::warning('Admin account removed', [
            'by'    => Auth::guard('admin')->user()->email,
            'admin' => $email,
        ]);

        return redirect()->route('admin.staff')
            ->with('success', "{$email} no longer has admin access.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Admin $admin = null): array
    {
        return $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'email'         => ['required', 'email', 'max:190', Rule::unique('admins', 'email')->ignore($admin?->id)],
            'password'      => [$admin ? 'nullable' : 'required', 'confirmed', Password::min(12)->letters()->numbers()],
            'is_super'      => ['sometimes', 'boolean'],
            'status'        => ['required', Rule::in(['active', 'suspended'])],
            'permissions'   => ['sometimes', 'array'],
            'permissions.*' => [Rule::in(array_keys(Admin::PERMISSIONS))],
        ]);
    }
}
