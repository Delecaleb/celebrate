<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Which gateway takes a payment, and whether it is allowed to.
 *
 * The routing rule used to be repeated at every checkout — USD through Stripe,
 * everything else through Paystack. It lives here now, next to the switches an
 * admin flips in Settings → Payments, so the two can never disagree about which
 * gateway a currency is waiting on.
 *
 * Naira has two gateways. Paystack leads when it is on, and AlatPay stands
 * behind it — so switching Paystack off makes AlatPay the checkout, and leaving
 * both on means AlatPay catches anything Paystack cannot start. Order is the
 * whole configuration: the first one switched on wins.
 *
 * Switching a gateway off stops new checkouts only. Payments already under way
 * still settle: webhooks, callbacks and payments:reconcile do not ask this
 * class, because refusing to record money that has already moved would be far
 * worse than the pause.
 */
final class PaymentGateways
{
    public const PAYSTACK = 'paystack';
    public const STRIPE   = 'stripe';
    public const ALATPAY  = 'alatpay';

    /**
     * Every gateway that can take this currency, best first.
     *
     * @return array<int, string>
     */
    public static function chainFor(?string $currency): array
    {
        return strtoupper((string) $currency) === 'USD'
            ? [self::STRIPE]
            : [self::PAYSTACK, self::ALATPAY];
    }

    /**
     * The ones actually switched on for this currency, in the order to try
     * them: the first is the checkout, anything after it is the fallback.
     *
     * @return array<int, string>
     */
    public static function activeFor(?string $currency): array
    {
        return array_values(array_filter(self::chainFor($currency), fn ($gateway) => self::isActive($gateway)));
    }

    /**
     * The gateway a checkout in this currency goes through.
     *
     * The first one switched on, or — when none is — the one this currency
     * would normally use, so a refusal still names something sensible.
     */
    public static function forCurrency(?string $currency): string
    {
        return self::activeFor($currency)[0] ?? self::chainFor($currency)[0];
    }

    /** The gateway to try when the first one will not start a payment. */
    public static function fallbackFor(?string $currency): ?string
    {
        return self::activeFor($currency)[1] ?? null;
    }

    /**
     * Whether an admin has left this gateway on.
     *
     * Unset means on for the gateways that shipped first: an install that has
     * never opened the panel must not find every checkout closed. AlatPay is
     * the exception — it is off until somebody sets its keys and turns it on.
     */
    public static function isActive(string $gateway): bool
    {
        $value = config("services.{$gateway}.enabled");

        $default = $gateway !== self::ALATPAY;

        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /** Whether someone paying in this currency can reach a checkout at all. */
    public static function canCheckout(?string $currency): bool
    {
        return self::activeFor($currency) !== [];
    }

    public static function label(string $gateway): string
    {
        return [
            self::PAYSTACK => 'Paystack',
            self::STRIPE   => 'Stripe',
            self::ALATPAY  => 'AlatPay',
        ][$gateway] ?? ucfirst($gateway);
    }

    /**
     * The response for a checkout whose gateway is switched off, or null when
     * it may go ahead.
     *
     * 503 rather than 4xx: nothing is wrong with the request, the service is
     * paused. The message is written for the payer, who cannot fix it and
     * should not be told which company processes cards.
     */
    public static function refuseCheckout(?string $currency): ?JsonResponse
    {
        if (self::canCheckout($currency)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'code'    => 'gateway_inactive',
            'message' => 'Card payments in ' . strtoupper((string) $currency) . ' are paused right now. Please try again a little later.',
        ], 503);
    }
}
