<?php

namespace App\Http\Controllers;

use App\Mail\GiftReceivedMail;
use App\Mail\GiftSentMail;
use App\Models\Gift;
use App\Models\PlatformAvailableGift;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\PaymentFulfilmentService;
use App\Services\PaymentSystem\PaystackService;
use App\Services\PaymentSystem\StripeService;
use App\Services\PaymentSystem\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GiftController extends Controller
{
    /**
     * Nobody sends a hundred of anything by accident, and the cap keeps a
     * hand-edited request from billing a card for an absurd total.
     */
    private const MAX_QUANTITY = 99;

    public function __construct(
        private WalletService   $wallet,
        private CurrencyService $currency,
        private PaystackService $paystack,
        private StripeService   $stripe,
        private PaymentFulfilmentService $fulfilment,
    ) {}

    /** How many were asked for: absent means one, never zero. */
    private function quantityFrom(Request $request): int
    {
        return max(1, min(self::MAX_QUANTITY, (int) $request->input('quantity', 1)));
    }

    /** "Warm Hug" on its own, "Warm Hug × 3" when there is more than one. */
    private static function label(string $name, int $quantity): string
    {
        return $quantity > 1 ? "{$name} × {$quantity}" : $name;
    }

    // -------------------------------------------------------------------------
    // Send a platform gift using wallet balance
    // -------------------------------------------------------------------------

    public function send(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $request->validate([
            'platform_gift_id' => ['required', 'exists:platform_available_gifts,id'],
            'celebration_id'   => ['required', 'exists:celebrations,id'],
            'message'          => ['nullable', 'string', 'max:500'],
            'quantity'         => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_QUANTITY],
        ]);

        $user         = Auth::user();
        $platformGift = PlatformAvailableGift::with('prices')->findOrFail($request->platform_gift_id);
        $userCurrency = $this->currency->forUser($user);
        $priceUsd     = (float) $platformGift->gift_price;
        $walletType   = (strtoupper($userCurrency) === 'USD') ? 'global' : 'local';
        $quantity     = $this->quantityFrom($request);
        // The charge is whatever the gift costs in this currency — the price an
        // admin set for it if there is one, the converted default otherwise —
        // times how many were asked for. Multiplied here rather than trusting
        // any total the browser sends.
        $unitPrice    = $platformGift->priceIn($userCurrency);
        $price        = round($unitPrice * $quantity, 2);

        if (! $this->wallet->hasSufficientBalance($user, $price, $walletType)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance.',
                'code'    => 'insufficient_balance',
            ], 422);
        }

        $reference    = 'wallet-' . Str::uuid();

        $gift = Gift::create([
            'celebration_id'        => $request->celebration_id,
            'platform_gift_id'      => $platformGift->id,
            'quantity'              => $quantity,
            'sender_user_id'        => $user->id,
            'sender_name'           => trim($user->first_name . ' ' . $user->last_name),
            'sender_email'          => $user->email,
            'amount'                => $price,
            'currency'              => $userCurrency,
            'guest_currency'        => $userCurrency,
            'conversion_rate'       => 1.0,
            'payment_method'        => 'wallet',
            'transaction_reference' => $reference,
            'payment_status'        => 'paid',
            'message'               => $request->message,
            'is_anonymous'          => false,
        ]);

        $this->wallet->debit(
            user:             $user,
            amount:           $price,
            description:      'Gift sent: ' . self::label($platformGift->gift_name, $quantity),
            reference:        $reference,
            source:           $gift,
            originalAmount:   $price,
            originalCurrency: $userCurrency,
            walletType:       $walletType
        );

        // Credit celebration owner's wallet
        $celebration = $gift->celebration()->with('user')->first();
        if ($celebration) {
            $owner = $celebration->user;
            $this->wallet->credit(
                user:             $owner,
                amount:           $price,
                description:      'Gift received: ' . self::label($platformGift->gift_name, $quantity),
                // A wallet gift writes two ledger rows — money out of one
                // account and into another — and wallet_transactions.reference
                // is unique, so the two legs cannot share a reference. Sharing
                // one meant every wallet send died on the credit.
                reference:        $reference . '-in',
                source:           $gift,
                originalAmount:   $price,
                originalCurrency: $userCurrency,
                walletType:       $walletType
            );
        }

        $newBalanceDisplay = $this->wallet->balance($user, $walletType);
        $symbol            = config("currency.currencies.{$userCurrency}.symbol", $userCurrency);

        $gift->load('platformGift');

        if ($celebration?->user?->email) {
            Mail::to($celebration->user->email)->queue(
                new GiftReceivedMail($gift, $celebration)
            );
        }

        // A wallet gift is still a gift: the sender gets the same receipt as
        // someone who paid by card.
        if ($celebration && filter_var($gift->sender_email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($gift->sender_email)->queue(new GiftSentMail($gift, $celebration));
            } catch (\Throwable $e) {
                Log::warning('Gift receipt could not be queued', [
                    'gift_id' => $gift->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success'     => true,
            'message'     => '🎁 ' . self::label($platformGift->gift_name, $quantity) . ' sent successfully!',
            'new_balance' => $symbol . number_format($newBalanceDisplay, 2),
        ]);
    }

    // -------------------------------------------------------------------------
    // Initiate a direct payment for a platform gift (Stripe or Paystack)
    // -------------------------------------------------------------------------

    public function initiatePayment(Request $request)
    {
        // Paying by card needs no account — only a name and an email to send
        // the receipt to. Signing in is for paying out of a wallet.
        $guest = ! Auth::check();

        $request->validate([
            'platform_gift_id' => ['required', 'exists:platform_available_gifts,id'],
            'celebration_id'   => ['required', 'exists:celebrations,id'],
            'message'          => ['nullable', 'string', 'max:500'],
            'quantity'         => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_QUANTITY],
            'guest_name'       => [$guest ? 'required' : 'nullable', 'string', 'max:120'],
            'guest_email'      => [$guest ? 'required' : 'nullable', 'email', 'max:190'],
        ]);

        $user         = Auth::user();
        $senderName   = $guest ? $request->guest_name  : trim($user->first_name . ' ' . $user->last_name);
        $senderEmail  = $guest ? $request->guest_email : $user->email;

        $platformGift = PlatformAvailableGift::with('prices')->findOrFail($request->platform_gift_id);
        $userCurrency = $guest ? $this->currency->forVisitor() : $this->currency->forUser($user);
        $priceUsd     = (float) $platformGift->gift_price;
        $walletType   = (strtoupper($userCurrency) === 'USD') ? 'global' : 'local';
        $quantity     = $this->quantityFrom($request);
        $price        = round($platformGift->priceIn($userCurrency) * $quantity, 2);
        $reference    = 'gift-pay-' . Str::uuid();

        // Pre-create the gift record so the callback can find it
        $gift = Gift::create([
            'celebration_id'        => $request->celebration_id,
            'platform_gift_id'      => $platformGift->id,
            'quantity'              => $quantity,
            'sender_user_id'        => $user?->id,
            'sender_name'           => $senderName,
            'sender_email'          => $senderEmail,
            'amount'                => $price,
            'currency'              => $userCurrency,
            'guest_currency'        => $userCurrency,
            'conversion_rate'       => 1.0,
            'payment_method'        => 'card',
            'transaction_reference' => $reference,
            'payment_status'        => 'pending',
            'message'               => $request->message,
            'is_anonymous'          => false,
        ]);

        try {
            if ($walletType === 'global') {
                $checkoutUrl = $this->stripe->createCheckoutSession(
                    amount:     $price,
                    currency:   'USD',
                    successUrl: route('gift.stripe.success') . '?reference=' . $reference . '&session_id={CHECKOUT_SESSION_ID}',
                    cancelUrl:  route('celebrations.show', $gift->celebration->slug) . '?cancelled=1',
                    metadata:   ['gift_id' => $gift->id, 'reference' => $reference],
                );

                return response()->json([
                    'success'           => true,
                    'provider'          => 'stripe',
                    'authorization_url' => $checkoutUrl,
                    'reference'         => $reference,
                ]);
            }

            $txn = $this->paystack->initTransaction($price, $userCurrency, [
                'email'          => $senderEmail,
                'gift_id'        => $gift->id,
                'celebration_id' => $request->celebration_id,
                'reference'      => $reference,
            ]);

            // Update gift with the paystack-generated reference for callback lookup
            $gift->update(['transaction_reference' => $txn['reference']]);

            return response()->json([
                'success'           => true,
                'provider'          => 'paystack',

                // The browser opens the inline checkout with the access code
                // and keeps the payer on the celebration page. The hosted URL
                // stays in the response as the fallback for when the inline
                // script cannot load — an ad blocker, a locked-down network.
                // Absent if Paystack ever answers without one; the browser
                // then falls back to the hosted page rather than breaking.
                'access_code'       => $txn['access_code'] ?? null,
                'reference'         => $txn['reference'],
                'authorization_url' => $txn['authorization_url'],
            ]);
        } catch (\Throwable $e) {
            $gift->delete();
            Log::error('Gift payment initiation failed', [
                'provider' => $walletType === 'global' ? 'stripe' : 'paystack',
                'currency' => $userCurrency,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                // The generic line hides gateway misconfiguration, which looks
                // identical to a real failure. Surface it while debugging.
                'message' => config('app.debug')
                    ? 'Payment initialisation failed: ' . $e->getMessage()
                    : 'Payment initialisation failed. Please try again.',
            ], 502);
        }
    }

    /**
     * Settle a gift paid through the inline checkout.
     *
     * The browser tells us a payment finished; it does not get to say so
     * convincingly. Everything here is decided by asking Paystack directly,
     * exactly as the redirect callback does — the only difference is that the
     * answer comes back as JSON to a page the payer never left.
     *
     * Safe to call twice. fulfilGift() takes a lock and settles once, so this
     * racing the webhook is expected rather than a problem.
     */
    public function confirmPayment(Request $request)
    {
        $request->validate([
            'reference' => ['required', 'string', 'max:120'],
        ]);

        $reference = $request->input('reference');
        $gift      = Gift::where('transaction_reference', $reference)->first();

        if (! $gift) {
            return response()->json([
                'success' => false,
                'message' => 'We could not find that gift. If you were charged, contact support and quote ' . $reference . '.',
            ], 404);
        }

        try {
            $data = $this->paystack->verifyTransaction($reference);
        } catch (\Throwable $e) {
            Log::error('Inline gift verification could not reach Paystack', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);

            // The money may well have left. Say nothing that sounds like a
            // refusal — the webhook and payments:reconcile both still run.
            return response()->json([
                'success' => false,
                'pending' => true,
                'message' => 'Your payment went through but we could not confirm it just yet. It will appear on the page within a few minutes.',
            ], 202);
        }

        if (($data['status'] ?? null) !== 'success') {
            Log::warning('Inline gift payment was not successful at Paystack', [
                'reference' => $reference,
                'status'    => $data['status'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'That payment did not complete. Nothing has been charged.',
            ], 422);
        }

        $status = $this->fulfilment->fulfilGift($reference);

        return response()->json([
            'success'      => true,
            'already'      => $status === PaymentFulfilmentService::ALREADY,
            'message'      => $status === PaymentFulfilmentService::ALREADY
                ? 'Your gift was already processed!'
                : '🎁 Your gift has been sent successfully!',
            'redirect_url' => route('celebrations.show', $gift->celebration->slug),
        ]);
    }

    // -------------------------------------------------------------------------
    // Paystack callback — verify and fulfil the pending gift
    // -------------------------------------------------------------------------

    public function paystackCallback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (! $reference) {
            return redirect()->back()->with('error', 'Invalid payment reference.');
        }

        $secretKey = config('services.paystack.secret');
        $response  = Http::withToken($secretKey)
            ->get("https://api.paystack.co/transaction/verify/{$reference}");

        if (! $response->successful() || $response->json('data.status') !== 'success') {
            Log::warning('Paystack verification failed', [
                'reference' => $reference,
                'response'  => $response->json(),
            ]);

            return redirect()->back()->with('error', 'Payment could not be verified. Please contact support.');
        }

        $gift = Gift::where('transaction_reference', $reference)->first();

        if (! $gift) {
            return redirect()->back()->with('error', 'Gift record not found.');
        }

        // The webhook may already have done this, or may be doing it right now.
        // The service takes a lock and settles it once, whoever gets there first.
        $status = $this->fulfilment->fulfilGift($reference);

        // ?gifted=1 is what tells the page to throw confetti. Only on a fresh
        // settlement — re-opening this link later should not re-celebrate.
        return redirect()
            ->route('celebrations.show', $status === PaymentFulfilmentService::ALREADY
                ? ['slug' => $gift->celebration->slug]
                : ['slug' => $gift->celebration->slug, 'gifted' => 1])
            ->with('success', $status === PaymentFulfilmentService::ALREADY
                ? 'Your gift was already processed!'
                : '🎁 Your gift has been sent successfully!');
    }

    // -------------------------------------------------------------------------
    // Stripe success callback — verify and fulfil the pending gift
    // -------------------------------------------------------------------------

    public function stripeSuccess(Request $request)
    {
        $reference = $request->query('reference');
        $sessionId = $request->query('session_id');

        if (! $reference) {
            return redirect()->route('dashboard')->with('error', 'Invalid payment reference.');
        }

        $gift = Gift::where('transaction_reference', $reference)->first();

        if (! $gift) {
            return redirect()->route('dashboard')->with('error', 'Gift record not found.');
        }

        if ($gift->payment_status === 'paid') {
            return redirect()
                ->route('celebrations.show', $gift->celebration->slug)
                ->with('success', 'Your gift was already processed!');
        }

        // Stripe has to confirm this before a single naira moves. Previously a
        // missing session id skipped the check, and a failed lookup was caught
        // and logged before falling through to mark the gift paid — so hitting
        // this URL with a known reference credited the owner for free.
        if (! $this->stripe->confirmPaidFor($sessionId, $reference)) {
            return redirect()
                ->route('celebrations.show', $gift->celebration->slug)
                ->with('error', 'We could not confirm that payment. If you were charged, contact support and quote ' . $reference . '.');
        }

        $status = $this->fulfilment->fulfilGift($reference);

        return redirect()
            ->route('celebrations.show', $status === PaymentFulfilmentService::ALREADY
                ? ['slug' => $gift->celebration->slug]
                : ['slug' => $gift->celebration->slug, 'gifted' => 1])
            ->with('success', $status === PaymentFulfilmentService::ALREADY
                ? 'Your gift was already processed!'
                : '🎁 Your gift has been sent successfully!');
    }
}
