<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Which card gateway takes a payment, and whether it is allowed to.
 *
 * The routing rule used to be repeated at every checkout — USD through Stripe,
 * everything else through Paystack. It lives here now, next to the switch an
 * admin flips in Settings → Payments, so the two can never disagree about
 * which gateway a currency is waiting on.
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

    /** The gateway a checkout in this currency goes through. */
    public static function forCurrency(?string $currency): string
    {
        return strtoupper((string) $currency) === 'USD' ? self::STRIPE : self::PAYSTACK;
    }

    /**
     * Whether an admin has left this gateway on.
     *
     * Unset means on: a fresh install, or one that has never opened the panel,
     * must not find every checkout closed.
     */
    public static function isActive(string $gateway): bool
    {
        $value = config("services.{$gateway}.enabled");

        if ($value === null || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
    }

    /** Whether someone paying in this currency can reach a checkout at all. */
    public static function canCheckout(?string $currency): bool
    {
        return self::isActive(self::forCurrency($currency));
    }

    public static function label(string $gateway): string
    {
        return [self::PAYSTACK => 'Paystack', self::STRIPE => 'Stripe'][$gateway] ?? ucfirst($gateway);
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
