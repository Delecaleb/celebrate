<?php

namespace App\Mail;

use App\Models\Celebration;
use App\Services\PaymentSystem\CurrencyService;

/**
 * What a celebration has received so far, for the celebrant's emails.
 *
 * Gifts and registry contributions arrive in whatever currency each giver paid
 * in, so they cannot simply be added up — a ₦8,000 gift and a $25 one are not
 * 8,025 of anything. Every paid row is converted into the celebrant's own
 * currency first, and the total is labelled with it. It used to be a raw sum
 * printed as "$… USD" whatever the money actually was.
 */
final class CelebrationTotals
{
    /**
     * @return array{amount: string, currency: string, gifts: int, contributions: int}
     */
    public static function received(Celebration $celebration): array
    {
        $currency = app(CurrencyService::class);
        $base     = strtoupper((string) config('currency.base'));
        $target   = strtoupper($celebration->user ? $currency->forUser($celebration->user) : $base);

        $inTarget = function ($amount, ?string $from) use ($currency, $base, $target): float {
            $from = strtoupper($from ?: $base);

            return $from === $target
                ? (float) $amount
                : $currency->convert((float) $amount, $from, $target);
        };

        $gifts         = $celebration->gifts()->where('payment_status', 'paid')->get();
        $contributions = $celebration->contributions()->where('payment_status', 'paid')->get();

        $total = $gifts->sum(fn ($gift) => $inTarget($gift->amount, $gift->currency))
            + $contributions->sum(fn ($contribution) => $inTarget($contribution->amount, $contribution->currency));

        return [
            'amount'        => GiftLines::money($total, $target),
            'currency'      => $target,
            'gifts'         => (int) $gifts->sum(fn ($gift) => max(1, (int) $gift->quantity)),
            'contributions' => $contributions->count(),
        ];
    }
}
