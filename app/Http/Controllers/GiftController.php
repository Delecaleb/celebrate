<?php

namespace App\Http\Controllers;

use App\Mail\GiftReceivedMail;
use App\Models\Gift;
use App\Models\PlatformAvailableGift;
use App\Services\PaymentSystem\CurrencyService;
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
    public function __construct(
        private WalletService   $wallet,
        private CurrencyService $currency,
        private PaystackService $paystack,
        private StripeService   $stripe,
    ) {}

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
        ]);

        $user         = Auth::user();
        $platformGift = PlatformAvailableGift::findOrFail($request->platform_gift_id);
        $userCurrency = $this->currency->forUser($user);
        $priceUsd     = (float) $platformGift->gift_price;
        $walletType   = (strtoupper($userCurrency) === 'USD') ? 'global' : 'local';
        $price        = $walletType === 'global' ? $priceUsd : $this->currency->convert($priceUsd, 'USD', $userCurrency);

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
            'sender_user_id'        => $user->id,
            'sender_name'           => $user->first_name . ' ' . $user->last_name,
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
            description:      "Gift sent: {$platformGift->gift_name}",
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
                description:      "Gift received: {$platformGift->gift_name}",
                reference:        $reference,
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

        return response()->json([
            'success'     => true,
            'message'     => "🎁 {$platformGift->gift_name} sent successfully!",
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
            'guest_name'       => [$guest ? 'required' : 'nullable', 'string', 'max:120'],
            'guest_email'      => [$guest ? 'required' : 'nullable', 'email', 'max:190'],
        ]);

        $user         = Auth::user();
        $senderName   = $guest ? $request->guest_name  : trim($user->first_name . ' ' . $user->last_name);
        $senderEmail  = $guest ? $request->guest_email : $user->email;

        $platformGift = PlatformAvailableGift::findOrFail($request->platform_gift_id);
        $userCurrency = $guest ? $this->currency->forVisitor() : $this->currency->forUser($user);
        $priceUsd     = (float) $platformGift->gift_price;
        $walletType   = (strtoupper($userCurrency) === 'USD') ? 'global' : 'local';
        $price        = $walletType === 'global' ? $priceUsd : $this->currency->convert($priceUsd, 'USD', $userCurrency);
        $reference    = 'gift-pay-' . Str::uuid();

        // Pre-create the gift record so the callback can find it
        $gift = Gift::create([
            'celebration_id'        => $request->celebration_id,
            'platform_gift_id'      => $platformGift->id,
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

        if ($gift->payment_status === 'paid') {
            return redirect()
                ->route('celebrations.show', $gift->celebration->slug)
                ->with('success', 'Your gift was already processed!');
        }

        $gift->update(['payment_status' => 'paid']);

        // Credit celebration owner's local wallet
        $celebration = $gift->celebration()->with('user')->first();
        if ($celebration) {
            $owner = $celebration->user;
            $this->wallet->credit(
                user:             $owner,
                amount:           $gift->amount,
                description:      "Gift received: " . ($gift->platformGift?->gift_name ?? "Platform Gift"),
                reference:        $gift->transaction_reference,
                source:           $gift,
                originalAmount:   $gift->amount,
                originalCurrency: $gift->currency,
                walletType:       'local'
            );
        }

        if ($celebration?->user?->email) {
            Mail::to($celebration->user->email)->queue(
                new GiftReceivedMail($gift->load('platformGift'), $celebration)
            );
        }

        return redirect()
            ->route('celebrations.show', $gift->celebration->slug)
            ->with('success', '🎁 Your gift has been sent successfully!');
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

        $gift->update(['payment_status' => 'paid']);

        // Credit celebration owner's global wallet
        $celebration = $gift->celebration()->with('user')->first();
        if ($celebration) {
            $owner = $celebration->user;
            $this->wallet->credit(
                user:             $owner,
                amount:           $gift->amount,
                description:      "Gift received: " . ($gift->platformGift?->gift_name ?? "Platform Gift"),
                reference:        $gift->transaction_reference,
                source:           $gift,
                originalAmount:   $gift->amount,
                originalCurrency: $gift->currency,
                walletType:       'global'
            );
        }

        if ($celebration?->user?->email) {
            Mail::to($celebration->user->email)->queue(
                new GiftReceivedMail($gift->load('platformGift'), $celebration)
            );
        }

        return redirect()
            ->route('celebrations.show', $gift->celebration->slug)
            ->with('success', '🎁 Your gift has been sent successfully!');
    }
}
