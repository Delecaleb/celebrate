<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What one gift costs in one currency.
 *
 * Only currencies an admin has priced deliberately have a row here; everything
 * else falls back to converting the gift's base price.
 */
class GiftPrice extends Model
{
    protected $fillable = ['platform_gift_id', 'currency', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function gift(): BelongsTo
    {
        return $this->belongsTo(PlatformAvailableGift::class, 'platform_gift_id');
    }
}
