<?php

namespace App\Models;

use App\Services\PaymentSystem\CurrencyService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wish extends Model
{
    use HasFactory;

    // Removed registry items keep their contribution history.
    use SoftDeletes;

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

    /**
     * What has actually been raised for this item, in the currency asked for.
     *
     * Summed from the contributions themselves, never read off current_amount.
     *
     * current_amount is held in the item's own currency, converted at whatever
     * the live rate happened to be on the day each payment landed. Reading it
     * back out through the item's frozen conversion_rate therefore returns a
     * figure nobody ever paid — two different rates applied to the same money.
     * On a real page that overstated the total by 22%.
     *
     * Here each contribution is converted exactly once, from the currency it
     * was charged in. A naira contributor's naira count as naira on a naira
     * page, which is the only answer a contributor can check against their own
     * bank statement.
     *
     * @param  iterable<int, WishContribution>|null  $paid  already-loaded paid
     *         rows for this item, so a page listing many items does not go back
     *         to the database for each one.
     */
    public function raisedIn(string $currency, ?CurrencyService $service = null, ?iterable $paid = null): float
    {
        $service ??= app(CurrencyService::class);
        $paid    ??= $this->contributions()->where('payment_status', 'paid')->get();
        $base      = config('currency.base');

        $total = 0.0;

        foreach ($paid as $contribution) {
            $total += $service->convert(
                (float) $contribution->amount,
                $contribution->currency ?: $base,
                $currency
            );
        }

        return round($total, 2);
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
