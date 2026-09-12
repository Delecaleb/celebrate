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

    /** A basket is a handful of gifts, not the whole catalogue. */
    private const MAX_LINES = 20;

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

    /**
     * The basket, as [platform_gift_id => quantity].
     *
     * Accepts either shape: an items[] array from the picker, or the older
     * single platform_gift_id + quantity, which the mobile client and every
     * existing link still send. Repeats of the same gift are folded together
     * so one basket never produces two rows for one gift.
     *
     * @return array<int, int>
     */
    private function basketFrom(Request $request): array
    {
        $items = $request->input('items');

        if (! is_array($items) || $items === []) {
            return [(int) $request->input('platform_gift_id') => $this->quantityFrom($request)];
        }

        $basket = [];

        foreach ($items as $item) {
            $id = (int) ($item['platform_gift_id'] ?? 0);

            if ($id <= 0) {
                continue;
            }

            $quantity = max(1, min(self::MAX_QUANTITY, (int) ($item['quantity'] ?? 1)));

            $basket[$id] = min(self::MAX_QUANTITY, ($basket[$id] ?? 0) + $quantity);
        }

        return $basket;
    }

    /**
     * Rules shared by both payment routes.
     *
     * items[] and platform_gift_id are alternatives, so neither can simply be
     * required — required_without keeps a request that names no gift at all
     * from reaching the pricing code.
     *
     * @return array<string, mixed>
     */
    private function basketRules(): array
    {
        return [
            'platform_gift_id' => ['required_without:items', 'nullable', 'exists:platform_available_gifts,id'],
            'quantity'         => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_QUANTITY],

            'items'                      => ['required_without:platform_gift_id', 'nullable', 'array', 'min:1', 'max:' . self::MAX_LINES],
            'items.*.platform_gift_id'   => ['required', 'exists:platform_available_gifts,id'],
            'items.*.quantity'           => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_QUANTITY],
        ];
    }

    /**
     * Price a basket against the catalogue.
     *
     * Everything that decides money happens here, from the gift rows the
     * server loaded itself — the request only ever says which gifts and how
     * many.
     *
     * @param  array<int, int>  $basket
     * @return array{0: \Illuminate\Support\Collection, 1: float}
     */
    private function priceBasket(array $basket, string $currency): array
    {
        $gifts = PlatformAvailableGift::with('prices')
            ->whereIn('id', array_keys($basket))
            ->get();

        abort_if($gifts->isEmpty(), 422, 'No gift was selected.');

        $lines = $gifts->map(function (PlatformAvailableGift $gift) use ($basket, $currency) {
            $quantity = $basket[$gift->id];

            return (object) [
                'gift'     => $gift,
                'quantity' => $quantity,
                'amount'   => round($gift->priceIn($currency) * $quantity, 2),
            ];
        })->values();

        return [$lines, round($lines->sum('amount'), 2)];
    }

    /** "Warm Hug × 3" for one line, "3 gifts" once there are several. */
    private static function basketLabel(\Illuminate\Support\Collection $lines): string
    {
        if ($lines->count() === 1) {
            $line = $lines->first();

            return self::label($line->gift->gift_name, $line->quantity);
        }

        $items = (int) $lines->sum('quantity');

        return $items . ' gifts';
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

        $request->validate($this->basketRules() + [
            'celebration_id' => ['required', 'exists:celebrations,id'],
            'message'        => ['nullable', 'string', 'max:500'],
        ]);

        $user         = Auth::user();
        $userCurrency = $this->currency->forUser($user);
        $walletType   = (strtoupper($userCurrency) === 'USD') ? 'global' : 'local';

        // Everything about the money is worked out from the catalogue, not
        // from the request: the browser only says which gifts and how many.
        [$lines, $total] = $this->priceBasket($this->basketFrom($request), $userCurrency);

        if (! $this->wallet->hasSufficientBalance($user, $total, $walletType)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance.',
                'code'    => 'insufficient_balance',
            ], 422);
        }

        $reference   = 'wallet-' . Str::uuid();
        $senderName  = trim($user->first_name . ' ' . $user->last_name);
        $basketLabel = self::basketLabel($lines);

        // One row per gift, all sharing the reference — that is what makes it
        // one basket rather than several unrelated sends.
        $gifts = $lines->map(fn ($line) => Gift::create([
            'celebration_id'        => $request->celebration_id,
            'platform_gift_id'      => $line->gift->id,
            'quantity'              => $line->quantity,
            'sender_user_id'        => $user->id,
            'sender_name'           => $senderName,
            'sender_email'          => $user->email,
            'amount'                => $line->amount,
            'currency'              => $userCurrency,
            'guest_currency'        => $userCurrency,
            'conversion_rate'       => 1.0,
            'payment_method'        => 'wallet',
            'transaction_reference' => $reference,
            'payment_status'        => 'paid',
            'message'               => $request->message,
            'is_anonymous'          => false,
        ]));

        // One debit for what was actually paid, however many lines it covered.
        $this->wallet->debit(
            user:             $user,
            amount:           $total,
            description:      'Gift sent: ' . $basketLabel,
            reference:        $reference,
            source:           $gifts->first(),
            originalAmount:   $total,
            originalCurrency: $userCurrency,
            walletType:       $walletType
        );

        // Credit celebration owner's wallet
        $celebration = $gifts->first()->celebration()->with('user')->first();

        if ($celebration?->user) {
            foreach ($gifts as $gift) {
                $this->wallet->credit(
                    user:             $celebration->user,
                    amount:           (float) $gift->amount,
                    description:      'Gift received: ' . $gift->load('platformGift')->label(),
                    // A wallet gift writes ledger rows on both sides, and
                    // wallet_transactions.reference is unique — so the legs
                    // cannot share one. Sharing it meant every wallet send
                    // died on the credit.
                    reference:        $reference . '-in-' . $gift->id,
                    source:           $gift,
                    originalAmount:   (float) $gift->amount,
                    originalCurrency: $userCurrency,
                    walletType:       $walletType
                );
            }
        }

        $newBalanceDisplay = $this->wallet->balance($user, $walletType);
        $symbol            = config("currency.currencies.{$userCurrency}.symbol", $userCurrency);

        $gifts->each->load('platformGift');

        if ($celebration?->user?->email) {
            Mail::to($celebration->user->email)->queue(
                new GiftReceivedMail($gifts, $celebration)
            );
        }

        // A wallet gift is still a gift: the sender gets the same receipt as
        // someone who paid by card.
        if ($celebration && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($user->email)->queue(new GiftSentMail($gifts, $celebration));
            } catch (\Throwable $e) {
                Log::warning('Gift receipt could not be queued', [
                    'reference' => $reference,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success'     => true,
            'message'     => '🎁 ' . $basketLabel . ' sent successfully!',
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

        $request->validate($this->basketRules() + [
            'celebration_id' => ['required', 'exists:celebrations,id'],
            'message'        => ['nullable', 'string', 'max:500'],
            'guest_name'     => [$guest ? 'required' : 'nullable', 'string', 'max:120'],
            'guest_email'    => [$guest ? 'required' : 'nullable', 'email', 'max:190'],
        ]);

        $user         = Auth::user();
        $senderName   = $guest ? $request->guest_name  : trim($user->first_name . ' ' . $user->last_name);
        $senderEmail  = $guest ? $request->guest_email : $user->email;

        $userCurrency = $guest ? $this->currency->forVisitor() : $this->currency->forUser($user);
        $walletType   = (strtoupper($userCurrency) === 'USD') ? 'global' : 'local';
        $reference    = 'gift-pay-' . Str::uuid();

        [$lines, $price] = $this->priceBasket($this->basketFrom($request), $userCurrency);

        // Pre-create the gift records so the callback can find them. They all
        // carry the one reference, which is what makes them a single basket
        // paid for once.
        $gifts = $lines->map(fn ($line) => Gift::create([
            'celebration_id'        => $request->celebration_id,
            'platform_gift_id'      => $line->gift->id,
            'quantity'              => $line->quantity,
            'sender_user_id'        => $user?->id,
            'sender_name'           => $senderName,
            'sender_email'          => $senderEmail,
            'amount'                => $line->amount,
            'currency'              => $userCurrency,
            'guest_currency'        => $userCurrency,
            'conversion_rate'       => 1.0,
            'payment_method'        => 'card',
            'transaction_reference' => $reference,
            'payment_status'        => 'pending',
            'message'               => $request->message,
            'is_anonymous'          => false,
        ]));

        $gift = $gifts->first();

        try {
            if ($walletType === 'global') {
                $checkoutUrl = $this->stripe->createCheckoutSession(
                    amount:     $price,
                    currency:   'USD',
                    successUrl: route('gift.stripe.success') . '?reference=' . $reference . '&session_id={CHECKOUT_SESSION_ID}',
                    cancelUrl:  route('celebrations.show', $gift->celebration->slug) . '?cancelled=1',
                    metadata:   ['gift_id' => $gift->id, 'reference' => $reference, 'lines' => $gifts->count()],
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

            // Paystack mints its own reference; every row in the basket has to
            // move onto it together or the callback will only find some of them.
            Gift::where('transaction_reference', $reference)
                ->update(['transaction_reference' => $txn['reference']]);

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
            // Nothing was charged, so leave no pending rows behind.
            Gift::where('transaction_reference', $reference)->delete();

            Log::error('Gift payment initiation failed', [
                'provider' => $walletType === 'global' ? 'stripe' : 'paystack',
                'currency' => $userCurrency,
                'lines'    => $lines->count(),
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
