<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * One operator-managed setting.
 *
 * Secrets are encrypted with APP_KEY on the way in and decrypted on the way
 * out, so a database dump does not hand somebody your live gateway keys.
 */
class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'is_encrypted', 'updated_by'];

    protected function casts(): array
    {
        return ['is_encrypted' => 'boolean'];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    /**
     * The usable value — decrypted where it was stored encrypted.
     *
     * A key that cannot be decrypted (APP_KEY rotated, row copied between
     * environments) returns null rather than throwing: a broken secret should
     * fall back to .env, not take the site down.
     */
    public function plainValue(): ?string
    {
        if ($this->value === null || $this->value === '') {
            return null;
        }

        if (! $this->is_encrypted) {
            return $this->value;
        }

        try {
            return Crypt::decryptString($this->value);
        } catch (\Throwable) {
            report(new \RuntimeException("Setting {$this->group}.{$this->key} could not be decrypted"));

            return null;
        }
    }
}
