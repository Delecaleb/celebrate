<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * A record of something a member of staff did.
 *
 * Only super admins can read these — see the /admin/audit route. Customers
 * never see them: an admin opening an account is a matter for whoever runs the
 * platform, not something to put in the customer's own activity feed.
 *
 * Append-only. There is no updated_at and nothing in the app edits a row.
 */
class AdminAuditLog extends Model
{
    public const UPDATED_AT = null;

    /** Actions worth naming, for the filter on the audit page. */
    public const ACTIONS = [
        'admin.impersonation.start' => 'Started viewing an account',
        'admin.impersonation.stop'  => 'Stopped viewing an account',
        'admin.staff.created'       => 'Added an admin',
        'admin.staff.updated'       => 'Changed an admin',
        'admin.staff.deleted'       => 'Removed an admin',
        'admin.withdrawal.approved' => 'Approved a withdrawal',
        'admin.withdrawal.rejected' => 'Rejected a withdrawal',
        'admin.payment.revalidated' => 'Re-checked a payment',
        'admin.gift.created'        => 'Added a gift',
        'admin.gift.updated'        => 'Changed a gift',
        'admin.gift.deleted'        => 'Deleted a gift',
        'admin.settings.updated'    => 'Changed settings',
        'admin.settings.tested'     => 'Tested settings',
        'admin.currency.created'    => 'Added a currency',
        'admin.currency.updated'    => 'Changed a currency',
        'admin.currency.deleted'    => 'Removed a currency',
    ];

    protected $fillable = [
        'admin_id',
        'admin_email',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'subject_label',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    /**
     * Write an entry for whoever is signed in to the panel.
     *
     * Never throws: an audit write failing must not take down the action it
     * was recording, and the surrounding controllers already log to the file
     * log as well.
     */
    public static function record(
        string $action,
        string $description,
        ?Model $subject = null,
        ?string $subjectLabel = null,
    ): void {
        try {
            $admin = Auth::guard('admin')->user();

            static::create([
                'admin_id'      => $admin?->id,
                'admin_email'   => $admin?->email ?? 'console',
                'action'        => $action,
                'description'   => $description,
                'subject_type'  => $subject ? $subject::class : null,
                'subject_id'    => $subject?->getKey(),
                'subject_label' => $subjectLabel,
                'ip_address'    => Request::ip(),
                'user_agent'    => substr((string) Request::userAgent(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function actionLabel(): string
    {
        return self::ACTIONS[$this->action] ?? $this->action;
    }
}
