<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Hands a gateway redirect back to the mobile app.
 *
 * Neither Paystack nor Stripe will redirect to a custom scheme like
 * celebratemi://, and Stripe only substitutes {CHECKOUT_SESSION_ID} into an
 * http(s) success_url. So both gateways are pointed at this plain web route,
 * which turns round and 302s to the app.
 *
 * It deliberately does no verifying and credits nothing — there is no session
 * here, and anyone can hit the URL. The app reads the params off the deep link
 * and calls the authenticated /verify endpoint, which is what actually checks
 * with the gateway and moves money.
 */
class PaymentBridgeController extends Controller
{
    public function __invoke(Request $request)
    {
        $scheme = config('app.mobile_scheme', 'celebratemi');

        $params = array_filter([
            'reference'  => $request->query('reference') ?? $request->query('trxref'),
            'session_id' => $request->query('session_id'),
            // Which flow to resume: wallet | gift | wish
            'flow'       => $request->query('flow'),
            'slug'       => $request->query('slug'),
            'status'     => $request->query('cancelled') ? 'cancelled' : 'returned',
        ], fn ($v) => $v !== null && $v !== '');

        return redirect()->away($scheme.'://payment/return?'.http_build_query($params));
    }
}
