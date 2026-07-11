<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Wish extends Model
{
    use HasFactory;

    protected $fillable = [
        'celebration_id',
        'name',
        'description',
        'wish_type',
        'target_amount',
        'amount_base',
        'base_currency',
        'amount_converted',
        'converted_currency',
        'conversion_rate',
        'current_amount',
        'currency',
        'wish_image',
        'wish_link',
        'priority_level',
        'status',
        'allow_partial_contribution',
        'contribution_count',
    ];

    protected $casts = [
        'target_amount'              => 'decimal:2',
        'amount_base'                => 'decimal:2',
        'amount_converted'           => 'decimal:2',
        'conversion_rate'            => 'decimal:6',
        'current_amount'             => 'decimal:2',
        'allow_partial_contribution' => 'boolean',
    ];

    /**
     * Return the display amount for a given currency.
     * Matches the stored base or converted amount directly,
     * or converts on-the-fly from base for any other currency.
     */
    public function displayAmount(string $currency): float
    {
        if ($currency === $this->base_currency) {
            return (float) ($this->amount_base ?? $this->target_amount ?? 0);
        }

        if ($currency === $this->converted_currency) {
            return (float) ($this->amount_converted ?? $this->target_amount ?? 0);
        }

        // Third currency: convert live from base
        $base = (float) ($this->amount_base ?? $this->target_amount ?? 0);

        return $base > 0
            ? app(\App\Services\PaymentSystem\CurrencyService::class)->convert($base, $this->base_currency ?? config('currency.base'), $currency)
            : 0.0;
    }

    public function celebration()
    {
        return $this->belongsTo(Celebration::class);
    }

    public function contributions()
    {
        return $this->hasMany(WishContribution::class);
    }

    public function replies()
    {
        return $this->hasMany(CommentReply::class);
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactionable');
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
