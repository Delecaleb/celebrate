<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

/**
 * A staff account for the admin panel.
 *
 * Deliberately not a User: these credentials open every celebration, payment
 * and payout on the platform, and they can sign in as a customer. Separate
 * table, separate guard, separate session.
 */
class Admin extends Authenticatable
{
    use HasFactory;

    /**
     * Everything an admin can be allowed to do.
     *
     * The catalogue lives here rather than in a table so that adding a
     * capability is a code change someone reviews, and so a stray row in
     * admin_permissions can never grant something that does not exist.
     *
     * @var array<string, array{label: string, group: string, note: string}>
     */
    public const PERMISSIONS = [
        'users.view' => [
            'label' => 'View users',
            'group' => 'Users',
            'note'  => 'See the user list, their celebrations and their wallet balances.',
        ],
        'users.impersonate' => [
            'label' => 'Sign in as a user',
            'group' => 'Users',
            'note'  => 'Open an account and see exactly what its owner sees. Read-only: money cannot be moved while signed in as someone else.',
        ],
        'users.suspend' => [
            'label' => 'Suspend users',
            'group' => 'Users',
            'note'  => 'Block or restore access to a customer account.',
        ],
        'events.view' => [
            'label' => 'View celebrations',
            'group' => 'Celebrations',
            'note'  => 'Every celebration page, public or private, and its registry.',
        ],
        'payments.view' => [
            'label' => 'View payments',
            'group' => 'Money',
            'note'  => 'Every gift, contribution and top-up — successful, pending or failed.',
        ],
        'payments.revalidate' => [
            'label' => 'Re-check payments',
            'group' => 'Money',
            'note'  => 'Ask the gateway again about a pending payment and settle it if it went through.',
        ],
        'withdrawals.view' => [
            'label' => 'View withdrawals',
            'group' => 'Money',
            'note'  => 'See payout requests and their status.',
        ],
        'withdrawals.process' => [
            'label' => 'Approve or reject withdrawals',
            'group' => 'Money',
            'note'  => 'Mark a payout complete, or reject it and refund the wallet.',
        ],
        'gifts.manage' => [
            'label' => 'Manage the gift catalogue',
            'group' => 'Content',
            'note'  => 'Add gifts, set their prices, and switch them between live and pending.',
        ],
        'frames.manage' => [
            'label' => 'Manage frames',
            'group' => 'Content',
            'note'  => 'Add and remove the photo frames offered on celebration pages.',
        ],
        'settings.manage' => [
            'label' => 'Manage settings and currencies',
            'group' => 'Platform',
            'note'  => 'Gateway keys, mail credentials, the location token, and which currencies the platform trades in. Give this to as few people as possible — these credentials move money.',
        ],
        'admins.manage' => [
            'label' => 'Manage admins',
            'group' => 'Staff',
            'note'  => 'Add staff accounts and set what they can reach. Super admins hold this always.',
        ],
    ];

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'is_super',
        'status',
        'created_by',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'is_super'      => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Admin $admin) {
            $admin->uuid ??= (string) Str::uuid();
        });
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(AdminPermission::class);
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    /**
     * Can this admin do the thing?
     *
     * A super admin can do everything, including things added after their
     * account was made — that is the point of the flag.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->is_super) {
            return true;
        }

        return $this->permissions
            ->pluck('permission')
            ->contains($permission);
    }

    /** @param array<int, string> $permissions */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Replace this admin's permissions with exactly this set.
     *
     * @param  array<int, string>  $permissions
     */
    public function syncPermissions(array $permissions, ?Admin $grantedBy = null): void
    {
        // Anything not in the catalogue is dropped rather than stored — an
        // unknown permission string would sit in the table for ever, look
        // meaningful in an audit, and grant nothing.
        $valid = array_values(array_intersect($permissions, array_keys(self::PERMISSIONS)));

        $this->permissions()->whereNotIn('permission', $valid)->delete();

        $existing = $this->permissions()->pluck('permission')->all();

        foreach (array_diff($valid, $existing) as $permission) {
            $this->permissions()->create([
                'permission' => $permission,
                'granted_by' => $grantedBy?->id,
            ]);
        }

        $this->load('permissions');
    }

    /**
     * The catalogue, arranged for a form.
     *
     * @return array<string, array<string, array{label: string, group: string, note: string}>>
     */
    public static function permissionsByGroup(): array
    {
        $grouped = [];

        foreach (self::PERMISSIONS as $key => $meta) {
            $grouped[$meta['group']][$key] = $meta;
        }

        return $grouped;
    }
}
