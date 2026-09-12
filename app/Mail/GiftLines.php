<?php

namespace App\Mail;

use App\Models\Gift;
use Illuminate\Support\Collection;

/**
 * Turning one or several gifts into the shape an email template wants.
 *
 * Both gift mails describe the same thing from opposite sides, and a basket
 * paid for in one go is lines on one mail rather than a mail per gift — so the
 * formatting lives here once instead of twice.
 */
final class GiftLines
{
    /** @param  Gift|Collection<int, Gift>  $gifts */
    public static function normalise(Gift|Collection $gifts): Collection
    {
        $collection = $gifts instanceof Gift ? collect([$gifts]) : $gifts->values();

        return $collection->each(fn (Gift $gift) => $gift->loadMissing('platformGift'));
    }

    /**
     * One row per gift: what it was, how many, what it cost.
     *
     * @param  Collection<int, Gift>  $gifts
     * @return array<int, array{label: string, amount: string}>
     */
    public static function lines(Collection $gifts): array
    {
        return $gifts->map(fn (Gift $gift) => [
            'label'  => $gift->label(),
            'amount' => self::money((float) $gift->amount, $gift->currency),
        ])->all();
    }

    /**
     * What was charged in total, in the currency it was charged in.
     *
     * Everything in one basket is paid for in a single transaction, so one
     * currency covers the lot.
     *
     * @param  Collection<int, Gift>  $gifts
     */
    public static function total(Collection $gifts): string
    {
        return self::money(
            (float) $gifts->sum(fn (Gift $gift) => (float) $gift->amount),
            $gifts->first()?->currency,
        );
    }

    /** "Ada Obi" addressed as "Ada"; an empty name addressed as nobody. */
    public static function firstName(?string $name): ?string
    {
        $name = trim((string) $name);

        return $name === '' ? null : explode(' ', $name)[0];
    }

    /** A naira gift is not a dollar figure. */
    private static function money(float $amount, ?string $currency): string
    {
        $code     = strtoupper($currency ?: (string) config('currency.base'));
        $symbol   = config("currency.currencies.{$code}.symbol", '');
        $decimals = (int) config("currency.currencies.{$code}.decimals", 2);

        return $symbol . number_format($amount, $decimals) . ' ' . $code;
    }
}
