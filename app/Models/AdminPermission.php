<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One capability granted to one admin.
 *
 * The set of valid values is Admin::PERMISSIONS — a row naming anything else
 * grants nothing, by design.
 */
class AdminPermission extends Model
{
    protected $fillable = ['admin_id', 'permission', 'granted_by'];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'granted_by');
    }

    /** The human label from the catalogue, or the raw key if it is unknown. */
    public function label(): string
    {
        return Admin::PERMISSIONS[$this->permission]['label'] ?? $this->permission;
    }
}
