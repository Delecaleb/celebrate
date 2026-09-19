<?php

namespace App\Http\Controllers;

use App\Models\Wish;
use App\Models\WishContribution;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\PaymentFulfilmentService;
use App\Services\PaymentSystem\WalletService;
use App\Services\PaymentSystem\StripeService;
use App\Services\PaymentSystem\PaystackService;
use App\Support\PaymentGateways;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WishContributionController extends Controller
{
    public function __construct(
        private WalletService   $wallet,
        private CurrencyService $currency,
        private StripeService   $stripe,
        private PaystackService $paystack,
        private PaymentFulfilmentService $fulfilment,
    ) {}

    // -------------------------------------------------------------------------
    // Contribute to a wish from wallet balance
    // -------------------------------------------------------------------------

    public function contributeFromWallet(Request $request, Wish $wish)
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $request->validate([
            'amount'   => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'message'  => ['nullable', 'string', 'max:500'],
        ]);

        $user            = Auth::user();
        $visitorCurrency = strtoupper($request->currency);
        $amountDisplay   = (float) $request->amount;
        $walletType      = ($visitorCurrency === 'USD') ? 'global' : 'local';

        if (! $this->wallet->hasSufficientBalance($user, $amountDisplay, $walletType)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance.',
                'code'    => 'insufficient_balance',
            ], 422);
        }

        $reference = 'wish-wallet-' . Str::uuid();

        $contribution = WishContribution::create([
            'wish_id'             => $wish->id,
            'celebration_id'      => $wish->celebration_id,
            'contributor_user_id' => $user->id,
            'contributor_name'    => trim($user->first_name . ' ' . $user->last_name),
            'contributor_email'   => $user->email,
            'amount'              => $amountDisplay,
            'currency'            => $visitorCurrency,
            'conversion_rate'     => 1.0,
            'original_amount'     => $amountDisplay,
            'contribution_type'   => 'cash',
            'message'             => $request->message,
            'payment_reference'   => $reference,
            'payment_status'      => 'paid',
            'is_anonymous'        => false,
        ]);

        $this->wallet->debit(
            user:             $user,
            amount:           $amountDisplay,
            description:      "Wish contribution: {$wish->name}",
            reference:        $reference,
            source:           $contribution,
            originalAmount:   $amountDisplay,
            originalCurrency: $visitorCurrency,
            walletType:       $walletType
        );

        // Credit celebration owner's wallet
        $owner = $wish->celebration->user;
        $this->wallet->credit(
            user:             $owner,
            amount:           $amountDisplay,
            description:      "Wish contribution from {$user->first_name}: {$wish->name}",
            // Two legs, one transfer — and the reference column is unique, so
            // they cannot be the same. Sharing one killed every wallet
            // contribution on the credit.
            reference:        $reference . '-in',
            source:           $contribution,
            originalAmount:   $amountDisplay,
            originalCurrency: $visitorCurrency,
            walletType:       $walletType
        );

        $incrementAmount = $amountDisplay;
        if (strtoupper($visitorCurrency) !== strtoupper($wish->currency)) {
            $incrementAmount = $this->currency->convert($amountDisplay, $visitorCurrency, $wish->currency);
        }
        $wish->increment('current_amount', $incrementAmount);
        $wish->increment('contribution_count');

        \App\Mail\ContributionReceivedMail::notifyCelebrant($contribution);

        $newBalanceDisplay = $this->wallet->balance($user, $walletType);
        $symbol            = config("currency.currencies.{$visitorCurrency}.symbol", $visitorCurrency);

        $fresh          = $wish->fresh();
        $targetDisplay  = $fresh->displayAmount($visitorCurrency);
        // Not current_amount: that is in the item's currency, and this response
        // is labelled with the visitor's symbol.
        $currentDisplay = $fresh->raisedIn($visitorCurrency, $this->currency);

        return response()->json([
            'success'     => true,
            'new_balance' => $symbol . number_format($newBalanceDisplay, 2),
            'progress'    => [
                'current' => round($currentDisplay, 2),
                'target'  => round($targetDisplay, 2),
                'pct'     => $targetDisplay > 0 ? min(100, round(($currentDisplay / $targetDisplay) * 100)) : 0,
                'symbol'  => $symbol,
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Initiate a Paystack payment for a wish contribution
    // -------------------------------------------------------------------------

    public function initiatePayment(Request $request, Wish $wish)
    {
        // Contributing by card needs no account — just a name and an email for
        // the receipt. Signing in is only for paying out of a wallet.
        $guest = ! Auth::check();

        $request->validate([
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'currency'    => ['required', 'string', 'size:3'],
            'message'     => ['nullable', 'string', 'max:500'],
            'guest_name'  => [$guest ? 'required' : 'nullable', 'string', 'max:120'],
            'guest_email' => [$guest ? 'required' : 'nullable', 'email', 'max:190'],
        ]);

        $user            = Auth::user();
        $contributorName = $guest ? $request->guest_name  : trim($user->first_name . ' ' . $user->last_name);
        $contributorMail = $guest ? $request->guest_email : $user->email;
        $visitorCurrency = strtoupper($request->currency);
        $amountDisplay   = (float) $request->amount;
        $reference       = 'wish-pay-' . Str::uuid();

        // A gateway an admin has switched off takes no new checkouts. Checked
        // before anything is written, so a refused payment leaves no pending row.
        if ($refusal = PaymentGateways::refuseCheckout($visitorCurrency)) {
            return $refusal;
        }

        $contribution = WishContribution::create([
            'wish_id'             => $wish->id,
            'celebration_id'      => $wish->celebration_id,
            'contributor_user_id' => $user?->id,
            'contributor_name'    => $contributorName,
            'contributor_email'   => $contributorMail,
            'amount'              => $amountDisplay,
            'currency'            => $visitorCurrency,
            'conversion_rate'     => 1.0,
            'original_amount'     => $amountDisplay,
            'contribution_type'   => 'cash',
            'message'             => $request->message,
            'payment_reference'   => $reference,
            'payment_status'      => 'pending',
            'is_anonymous'        => false,
        ]);

        if ($visitorCurrency === 'USD') {
            try {
                $checkoutUrl = $this->stripe->createCheckoutSession(
                    amount:     $amountDisplay,
                    currency:   'USD',
                    successUrl: route('wish.stripe.success') . '?reference=' . $reference . '&session_id={CHECKOUT_SESSION_ID}',
                    cancelUrl:  route('celebrations.show', $wish->celebration->slug) . '?cancelled=1',
                    metadata:   ['wish_id' => $wish->id, 'reference' => $reference],
                );

                return response()->json([
                    'success'           => true,
                    'provider'          => 'stripe',
                    'reference'         => $reference,
                    'authorization_url' => $checkoutUrl,
                ]);
            } catch (\Throwable $e) {
                $contribution->delete();
                Log::error('Stripe wish payment init failed', ['error' => $e->getMessage()]);

                return response()->json([
                    'success' => false,
                    // A dead gateway key is indistinguishable from a real
                    // failure behind the generic line — show it while debugging.
                    'message' => config('app.debug')
                        ? 'Stripe payment initialization failed: ' . $e->getMessage()
                        : 'Stripe payment initialization failed.',
                ], 502);
            }
        }

        $gateways = PaymentGateways::activeFor($visitorCurrency);

        // AlatPay only leads when Paystack is switched off; otherwise it waits
        // below, for a Paystack call that will not start.
        if (($gateways[0] ?? null) === PaymentGateways::ALATPAY) {
            return $this->alatPayTransfer($contribution, $wish, $amountDisplay, $visitorCurrency, $reference, $contributorName, $contributorMail);
        }

        $secretKey = config('services.paystack.secret');
        if (! $secretKey) {
            $contribution->delete();
            return response()->json(['success' => false, 'message' => 'Payment gateway is not configured.'], 503);
        }

        $response = Http::withToken($secretKey)
            ->post('https://api.paystack.co/transaction/initialize', [
                'email'        => $contributorMail,
                'amount'       => (int) round($amountDisplay * 100),
                'currency'     => $visitorCurrency,
                'reference'    => $reference,
                'callback_url' => route('wish.contribute.callback'),
                'metadata'     => [
                    'wish_name'      => $wish->name,
                    'wish_id'        => $wish->id,
                    'celebration_id' => $wish->celebration_id,
                ],
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            Log::error('Paystack wish payment init failed', $response->json());

            // The other gateway gets its turn before anyone sees an error.
            if (in_array(PaymentGateways::ALATPAY, $gateways, true)) {
                return $this->alatPayTransfer($contribution, $wish, $amountDisplay, $visitorCurrency, $reference, $contributorName, $contributorMail);
            }

            $contribution->delete();
            return response()->json([
                'success' => false,
                'message' => $response->json('message', 'Payment initialisation failed.'),
            ], 502);
        }

        return response()->json([
            'success'   => true,
            'provider'  => 'paystack',
            'reference' => $reference,

            // Lets the browser open Paystack's own frame over the registry
            // instead of sending the contributor away. The amount is already
            // fixed by the call above, so nothing about the charge is decided
            // in the page.
            'access_code'       => $response->json('data.access_code'),
            'authorization_url' => $response->json('data.authorization_url'),
        ]);
    }

    /**
     * Open an AlatPay account for this contribution and hand the details to
     * the page, which then waits for the transfer to land.
     */
    private function alatPayTransfer(
        WishContribution $contribution,
        Wish $wish,
        float $amount,
        string $currency,
        string $reference,
        string $name,
        string $email,
    ) {
        try {
            $account = app(\App\Services\PaymentSystem\AlatPayService::class)->createVirtualAccount(
                amount:      $amount,
                currency:    $currency,
                orderId:     $reference,
                customer:    ['email' => $email, 'first_name' => $name],
                description: 'Towards ' . $wish->name,
            );
        } catch (\Throwable $e) {
            Log::error('AlatPay wish payment init failed', ['error' => $e->getMessage()]);
            $contribution->delete();

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? 'AlatPay initialisation failed: ' . $e->getMessage()
                    : 'Payment initialisation failed. Please try again.',
            ], 502);
        }

        return response()->json(['success' => true, 'provider' => PaymentGateways::ALATPAY, 'reference' => $reference] + $account);
    }

    /**
     * Settle a contribution paid through the inline checkout.
     *
     * The browser's word counts for nothing here — the reference is checked
     * against Paystack before a naira moves, exactly as the redirect callback
     * does. Safe to call twice; fulfilWishContribution() takes a lock and
     * settles once, so racing the webhook is expected.
     */
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'reference' => ['required', 'string', 'max:120'],
        ]);

        $reference    = $request->input('reference');
        $contribution = WishContribution::where('payment_reference', $reference)->first();

        if (! $contribution) {
            return response()->json([
                'success' => false,
                'message' => 'We could not find that contribution. If you were charged, contact support and quote ' . $reference . '.',
            ], 404);
        }

        $secretKey = config('services.paystack.secret');
        $response  = Http::withToken($secretKey)
            ->get("https://api.paystack.co/transaction/verify/{$reference}");

        if (! $response->successful()) {
            Log::error('Inline contribution verification could not reach Paystack', [
                'reference' => $reference,
            ]);

            // The money may already be gone. Say nothing that sounds like a
            // refusal — the webhook and payments:reconcile still run.
            return response()->json([
                'success' => false,
                'pending' => true,
                'message' => 'Your payment went through but we could not confirm it just yet. It will appear on the page within a few minutes.',
            ], 202);
        }

        if ($response->json('data.status') !== 'success') {
            Log::warning('Inline contribution was not successful at Paystack', [
                'reference' => $reference,
                'status'    => $response->json('data.status'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'That payment did not complete. Nothing has been charged.',
            ], 422);
        }

        $status = $this->fulfilment->fulfilWishContribution($reference);

        return response()->json([
            'success'      => true,
            'already'      => $status === PaymentFulfilmentService::ALREADY,
            'message'      => $status === PaymentFulfilmentService::ALREADY
                ? 'That contribution was already processed!'
                : '🎉 Thank you — your contribution has been added!',
            'redirect_url' => route('celebrations.show', $contribution->celebration->slug),
        ]);
    }

    // -------------------------------------------------------------------------
    // Paystack callback — verify and fulfil the pending contribution
    // -------------------------------------------------------------------------

    public function paystackCallback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (! $reference || ! str_starts_with((string) $reference, 'wish-pay-')) {
            return redirect()->back()->with('error', 'Invalid payment reference.');
        }

        $secretKey = config('services.paystack.secret');
        $response  = Http::withToken($secretKey)
            ->get("https://api.paystack.co/transaction/verify/{$reference}");

        if (! $response->successful() || $response->json('data.status') !== 'success') {
            Log::warning('Paystack wish contribution verification failed', [
                'reference' => $reference,
                'response'  => $response->json(),
            ]);
            return redirect()->back()->with('error', 'Payment could not be verified. Please contact support.');
        }

        $contribution = WishContribution::where('payment_reference', $reference)->first();

        if (! $contribution) {
            return redirect()->back()->with('error', 'Contribution record not found.');
        }

        if ($contribution->payment_status === 'paid') {
            return redirect()
                ->route('celebrations.show', $contribution->celebration->slug)
                ->with('success', 'Your contribution was already processed!');
        }

        // Idempotent: the webhook may have settled this already, or be doing it
        // right now. Whichever gets the lock first does the work.
        $status = $this->fulfilment->fulfilWishContribution($reference);

        return redirect()
            ->route('celebrations.show', $contribution->celebration->slug)
            ->with('success', $status === PaymentFulfilmentService::ALREADY
                ? 'Your contribution was already processed!'
                : '🎉 Your contribution has been added — thank you!');
    }

    // -------------------------------------------------------------------------
    // Stripe callback — verify and fulfil the pending contribution
    // -------------------------------------------------------------------------

    public function stripeSuccess(Request $request)
    {
        $reference = $request->query('reference');
        $sessionId = $request->query('session_id');

        if (! $reference || ! str_starts_with((string) $reference, 'wish-pay-')) {
            return redirect()->route('dashboard')->with('error', 'Invalid payment reference.');
        }

        $contribution = WishContribution::where('payment_reference', $reference)->first();

        if (! $contribution) {
            return redirect()->route('dashboard')->with('error', 'Contribution record not found.');
        }

        if ($contribution->payment_status === 'paid') {
            return redirect()
                ->route('celebrations.show', $contribution->celebration->slug)
                ->with('success', 'Your contribution was already processed!');
        }

        // Nothing is credited until Stripe confirms this exact reference — a
        // missing session id or a failed lookup used to fall straight through
        // to marking the contribution paid.
        if (! $this->stripe->confirmPaidFor($sessionId, $reference)) {
            return redirect()
                ->route('celebrations.show', $contribution->celebration->slug)
                ->with('error', 'We could not confirm that payment. If you were charged, contact support and quote ' . $reference . '.');
        }

        $status = $this->fulfilment->fulfilWishContribution($reference);

        return redirect()
            ->route('celebrations.show', $contribution->celebration->slug)
            ->with('success', $status === PaymentFulfilmentService::ALREADY
                ? 'Your contribution was already processed!'
                : '🎉 Your contribution has been added — thank you!');
    }
}
