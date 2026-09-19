<?php

namespace App\Http\Controllers;

use App\Services\PaymentSystem\PaymentFulfilmentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Gateway webhooks.
 *
 * The browser coming back from a payment is a courtesy, not a guarantee: people
 * close the tab, lose signal, or the redirect never fires. These endpoints are
 * the only reliable notice that money moved, so every payment is fulfilled from
 * here as well — and the fulfilment service is idempotent, so whichever arrives
 * first wins and the second is a no-op.
 *
 * Both endpoints are exempt from CSRF (see bootstrap/app.php) and must
 * therefore prove the request really came from the gateway before doing
 * anything at all.
 */
class WebhookController extends Controller
{
    public function __construct(private PaymentFulfilmentService $fulfilment) {}

    /**
     * Paystack signs the raw body with HMAC-SHA512 using the secret key.
     */
    public function paystack(Request $request): Response
    {
        $secret    = (string) config('services.paystack.secret');
        $signature = (string) $request->header('x-paystack-signature', '');
        $payload   = $request->getContent();

        if ($secret === '' || ! hash_equals(hash_hmac('sha512', $payload, $secret), $signature)) {
            Log::warning('Paystack webhook rejected: bad signature', ['ip' => $request->ip()]);

            return response('Invalid signature', 401);
        }

        $event = $request->json('event');

        // Everything else — refunds, transfers, disputes — is acknowledged so
        // Paystack stops retrying, but not acted on here.
        if ($event !== 'charge.success') {
            return response('Ignored', 200);
        }

        $reference = (string) $request->json('data.reference', '');

        if ($reference === '') {
            return response('No reference', 200);
        }

        $status = $this->fulfilment->fulfil($reference);

        Log::info('Paystack webhook handled', ['reference' => $reference, 'status' => $status]);

        // A 200 even for an unknown reference: retrying will not make a
        // record appear, and the reconciliation sweep is the safety net.
        return response($status, 200);
    }

    /**
     * AlatPay calls the URL registered against the business when a transfer
     * lands.
     *
     * AlatPay publishes no signing scheme, so this never takes the payload's
     * word for it. Either the shared secret set in Settings matches, or the
     * transaction is read back from AlatPay before a penny is credited. With
     * neither available nothing is fulfilled — an unverified "you have been
     * paid" is exactly what a forger would send.
     */
    public function alatpay(Request $request): Response
    {
        $reference = (string) ($request->json('data.orderId') ?? $request->json('orderId') ?? '');
        $status    = (string) ($request->json('data.status') ?? $request->json('status') ?? '');
        $alatId    = (string) ($request->json('data.id') ?? $request->json('data.transactionId') ?? '');

        if ($reference === '') {
            return response('No order id', 200);
        }

        if (! \App\Services\PaymentSystem\AlatPayService::isPaid($status)) {
            Log::info('AlatPay webhook ignored', ['reference' => $reference, 'status' => $status]);

            return response('Ignored', 200);
        }

        if (! $this->alatPayIsTrustworthy($request, $alatId)) {
            Log::warning('AlatPay webhook could not be verified', ['reference' => $reference, 'ip' => $request->ip()]);

            return response('Unverified', 202);
        }

        $outcome = $this->fulfilment->fulfil($reference);

        Log::info('AlatPay webhook handled', ['reference' => $reference, 'status' => $outcome]);

        return response($outcome, 200);
    }

    /**
     * Either the secret proves who sent it, or AlatPay itself confirms the
     * payment when asked.
     */
    private function alatPayIsTrustworthy(Request $request, string $transactionId): bool
    {
        $secret = (string) (config('services.alatpay.webhook_secret') ?? '');

        if ($secret !== '') {
            $signature = (string) $request->header('x-signature', '');

            // base64 HMAC-SHA256 of the raw body, as AlatPay's own plugins
            // verify it. Compared in constant time.
            return hash_equals(base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true)), $signature);
        }

        if ($transactionId === '') {
            return false;
        }

        $remote = app(\App\Services\PaymentSystem\AlatPayService::class)->transactionStatus($transactionId);

        return $remote !== null
            && \App\Services\PaymentSystem\AlatPayService::isPaid((string) ($remote['status'] ?? ''));
    }

    /**
     * Stripe signs `timestamp.payload` with HMAC-SHA256 using the endpoint's
     * own signing secret — not the API key.
     */
    public function stripe(Request $request): Response
    {
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '') {
            Log::error('Stripe webhook received but STRIPE_WEBHOOK_SECRET is not set');

            return response('Not configured', 500);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature', ''),
                $secret,
                // Stripe's default tolerance, stated rather than implied.
                300
            );
        } catch (\UnexpectedValueException | \Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook rejected', ['ip' => $request->ip(), 'error' => $e->getMessage()]);

            return response('Invalid signature', 401);
        }

        if ($event->type !== 'checkout.session.completed') {
            return response('Ignored', 200);
        }

        $session = $event->data->object;

        // Only a paid session moves money — an unpaid one may still complete.
        if (($session->payment_status ?? null) !== 'paid') {
            return response('Not paid', 200);
        }

        $reference = $session->metadata->reference ?? null;

        if (! $reference) {
            Log::warning('Stripe webhook has no reference in metadata', ['session' => $session->id ?? null]);

            return response('No reference', 200);
        }

        $status = $this->fulfilment->fulfil($reference);

        Log::info('Stripe webhook handled', ['reference' => $reference, 'status' => $status]);

        return response($status, 200);
    }
}
