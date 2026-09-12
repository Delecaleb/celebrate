<?php

namespace App\Models;

use App\Services\PaymentSystem\CurrencyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A gift a guest can send from a celebration page.
 *
 * Prices are held in USD — the platform's base currency — and converted to
 * whatever the visitor works in at display time, so one catalogue serves every
 * market without a price per country.
 */
class PlatformAvailableGift extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE  = 'active';
    public const STATUS_PENDING = 'pending';

    /** Shown as filters in the admin, and as headings on the gift plate. */
    public const CATEGORIES = [
        'small'   => 'Small & sweet',
        'treats'  => 'Treats',
        'party'   => 'Party',
        'beauty'  => 'Beauty & self-care',
        'home'    => 'Home & everyday',
        'grand'   => 'Grand gestures',
    ];

    protected $fillable = [
        'gift_name',
        'gift_description',
        'gift_icon',
        'accent_color',
        'category',
        'gift_price',
        'gift_image_url',
        'gift_link_url',
        'is_active',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'gift_price' => 'decimal:2',
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // is_active is the column the guest-facing queries already read. Rather
        // than rewrite those and risk missing one, it is kept as a mirror of
        // the status an admin actually sets. Status is the source of truth.
        static::saving(function (self $gift) {
            $gift->is_active = $gift->status === self::STATUS_ACTIVE;
        });
    }

    /** Gifts a guest is allowed to see. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('gift_price');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * The icon to draw, always something.
     *
     * A gift with neither an icon nor an image would otherwise be an empty
     * square on the celebration page.
     */
    public function icon(): string
    {
        return $this->gift_icon ?: 'mdi-gift-outline';
    }

    public function accent(): string
    {
        return $this->accent_color ?: config('brand.primary.500', '#7c3aed');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Uncategorised';
    }

    /**
     * Prices an admin set deliberately, keyed by currency.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(GiftPrice::class, 'platform_gift_id');
    }

    /**
     * What this gift costs in a given currency.
     *
     * An explicit price wins. Otherwise the base price is converted, which is
     * what every currency did before any of them could be priced by hand — so
     * adding a currency to the config never leaves a gift unpriced.
     */
    public function priceIn(?string $currency = null): float
    {
        $currency = strtoupper($currency ?: self::baseCurrency());
        $explicit = $this->explicitPrice($currency);

        if ($explicit !== null) {
            return $explicit;
        }

        $base = (float) $this->gift_price;

        if ($currency === self::baseCurrency()) {
            return $base;
        }

        return round(app(CurrencyService::class)->convert($base, self::baseCurrency(), $currency), 2);
    }

    /**
     * The hand-set price for a currency, or null where there is none.
     *
     * Reads the loaded relation when it is there, so a list of gifts costs one
     * query rather than one per gift.
     */
    public function explicitPrice(string $currency): ?float
    {
        $currency = strtoupper($currency);

        $row = $this->relationLoaded('prices')
            ? $this->prices->firstWhere('currency', $currency)
            : $this->prices()->where('currency', $currency)->first();

        return $row ? (float) $row->amount : null;
    }

    public function hasExplicitPrice(string $currency): bool
    {
        return $this->explicitPrice($currency) !== null;
    }

    /**
     * Replace the hand-set prices with exactly this set.
     *
     * A blank or missing entry means "no explicit price" — the gift falls back
     * to converting the default, which is the behaviour an admin gets by simply
     * leaving a field empty.
     *
     * @param  array<string, mixed>  $prices  currency code => amount
     */
    public function syncPrices(array $prices): void
    {
        $supported = array_keys(config('currency.currencies', []));

        foreach ($supported as $currency) {
            $amount = $prices[$currency] ?? null;

            // The base price lives on the gift itself, not here — storing it
            // twice invites the two disagreeing.
            if ($currency === self::baseCurrency()) {
                continue;
            }

            if ($amount === null || $amount === '' || (float) $amount <= 0) {
                $this->prices()->where('currency', $currency)->delete();

                continue;
            }

            $this->prices()->updateOrCreate(
                ['currency' => $currency],
                ['amount' => round((float) $amount, 2)]
            );
        }

        // A currency dropped from the config keeps its row rather than being
        // deleted here — putting it back should not lose the price somebody set.
        $this->load('prices');
    }

    public static function baseCurrency(): string
    {
        return strtoupper((string) config('currency.base', 'USD'));
    }

    /**
     * Every currency the platform supports, in config order.
     *
     * @return array<string, array{symbol: string, name: string, decimals: int}>
     */
    public static function supportedCurrencies(): array
    {
        return config('currency.currencies', []);
    }

    /**
     * Roughly what this gift costs, in words.
     *
     * The catalogue deliberately runs from something anyone can afford to
     * something nobody sends by accident; this is what the admin list bands it
     * by. Thresholds are in USD, the currency prices are stored in.
     */
    public function tier(): string
    {
        $price = (float) $this->gift_price;

        return match (true) {
            $price < 3    => 'Everyday',
            $price < 15   => 'Treat',
            $price < 60   => 'Occasion',
            $price < 200  => 'Generous',
            default       => 'Luxury',
        };
    }
}
