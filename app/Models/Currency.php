<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A currency the platform trades in.
 *
 * Adding one here gives it a gift-price field, a wallet, and a country mapping
 * for signup — the app reads these rows into config at boot, so the rest of the
 * code carries on calling config('currency.*') as it always has.
 */
class Currency extends Model
{
    protected $fillable = [
        'code', 'name', 'symbol', 'decimals',
        'is_active', 'fallback_rate', 'countries', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'decimals'      => 'integer',
            'is_active'     => 'boolean',
            'fallback_rate' => 'decimal:6',
            'countries'     => 'array',
            'sort_order'    => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $currency) {
            $currency->code = strtoupper(trim($currency->code));
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('code');
    }

    public function isBase(): bool
    {
        return $this->code === strtoupper((string) config('currency.base'));
    }

    /** How many users hold this currency — what makes deactivating it risky. */
    public function usersCount(): int
    {
        return User::where('currency', $this->code)->count();
    }

    /** Gift prices set by hand in this currency. */
    public function giftPricesCount(): int
    {
        return GiftPrice::where('currency', $this->code)->count();
    }
}
