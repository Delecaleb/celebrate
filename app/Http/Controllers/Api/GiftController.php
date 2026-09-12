<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\GiftReceivedMail;
use App\Models\Gift;
use App\Models\PlatformAvailableGift;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\PaystackService;
use App\Services\PaymentSystem\StripeService;
use App\Services\PaymentSystem\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Sending a gift off the gift plate — out of the wallet, or by card.
 */
class GiftController extends Controller
{
    public function __construct(
        private CurrencyService $currency,
        private WalletService $wallet,
        private PaystackService $paystack,
        private StripeService $stripe,
    ) {}

    /**
     * Send a gift paid for out of the wallet. Settles immediately.
     */
    public function send(Request $request)
    {
        $data = $request->validate([
            'platform_gift_id' => ['required', 'exists:platform_available_gifts,id'],
            'celebration_id'   => ['required', 'exists:celebrations,id'],
            'message'          => ['nullable', 'string', 'max:500'],
        ]);

        $user         = $request->user();
        $platformGift = PlatformAvailableGift::with('prices')->findOrFail($data['platform_gift_id']);
        $userCurrency = $this->currency->forUser($user);
        $walletType   = strtoupper($userCurrency) === 'USD' ? 'global' : 'local';
        // Same rule as the web checkout: the hand-set price for this currency,
        // or the converted default.
        $price        = $platformGift->priceIn($userCurrency);

        if (! $this->wallet->hasSufficientBalance($user, $price, $walletType)) {
            return response()->json([
                'message' => 'Insufficient wallet balance.',
                'code'    => 'insufficient_balance',
            ], 422);
        }

        $reference = 'wallet-'.Str::uuid();

        $gift = DB::transaction(function () use ($data, $platformGift, $user, $userCurrency, $price, $reference, $walletType) {
            $gift = Gift::create([
                'celebration_id'        => $data['celebration_id'],
                'platform_gift_id'      => $platformGift->id,
                'sender_user_id'        => $user->id,
                'sender_name'           => trim($user->first_name.' '.$user->last_name),
                'sender_email'          => $user->email,
                'amount'                => $price,
                'currency'              => $userCurrency,
                'guest_currency'        => $userCurrency,
                'conversion_rate'       => 1.0,
                'payment_method'        => 'wallet',
                'transaction_reference' => $reference,
                'payment_status'        => 'paid',
                'message'               => $data['message'] ?? null,
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
                walletType:       $walletType,
            );

            $this->creditOwner($gift, $walletType);

            return $gift;
        });

        $this->notifyOwner($gift);

        $symbol = config("currency.currencies.{$userCurrency}.symbol", $userCurrency);

        return response()->json([
            'message'     => "🎁 {$platformGift->gift_name} sent successfully!",
            'new_balance' => round($this->wallet->balance($user->fresh(), $walletType), 2),
            'symbol'      => $symbol,
        ]);
    }

    /**
     * Pay for a gift by card. Guests may do this without an account, so the
     * route is public and a name/email are required when unauthenticated.
     */
    public function initiatePayment(Request $request)
    {
        $user  = $request->user();
        $guest = $user === null;

        $data = $request->validate([
            'platform_gift_id' => ['required', 'exists:platform_available_gifts,id'],
            'celebration_id'   => ['required', 'exists:celebrations,id'],
            'message'          => ['nullable', 'string', 'max:500'],
            'guest_name'       => [$guest ? 'required' : 'nullable', 'string', 'max:120'],
            'guest_email'      => [$guest ? 'required' : 'nullable', 'email', 'max:190'],
        ]);

        $platformGift = PlatformAvailableGift::with('prices')->findOrFail($data['platform_gift_id']);
        $currencyCode = $guest ? $this->currency->forVisitor() : $this->currency->forUser($user);
        $isUsd        = strtoupper($currencyCode) === 'USD';
        $price        = $platformGift->priceIn($currencyCode);
        $reference    = 'gift-pay-'.Str::uuid();

        $gift = Gift::create([
            'celebration_id'        => $data['celebration_id'],
            'platform_gift_id'      => $platformGift->id,
            'sender_user_id'        => $user?->id,
            'sender_name'           => $guest ? $data['guest_name'] : trim($user->first_name.' '.$user->last_name),
            'sender_email'          => $guest ? $data['guest_email'] : $user->email,
            'amount'                => $price,
            'currency'              => $currencyCode,
            'guest_currency'        => $currencyCode,
            'conversion_rate'       => 1.0,
            'payment_method'        => 'card',
            'transaction_reference' => $reference,
            'payment_status'        => 'pending',
            'message'               => $data['message'] ?? null,
            'is_anonymous'          => false,
        ]);

        $bridge = fn (array $extra = []) => route('payments.bridge', $extra + [
            'reference' => $reference,
            'flow'      => 'gift',
            'slug'      => $gift->celebration->slug,
        ]);

        try {
            if ($isUsd) {
                $url = $this->stripe->createCheckoutSession(
                    amount:     $price,
                    currency:   'USD',
                    successUrl: $bridge(['session_id' => '{CHECKOUT_SESSION_ID}']),
                    cancelUrl:  $bridge(['cancelled' => 1]),
                    metadata:   ['gift_id' => $gift->id, 'reference' => $reference],
                );

                return response()->json([
                    'provider'          => 'stripe',
                    'reference'         => $reference,
                    'authorization_url' => $url,
                ]);
            }

            $txn = $this->paystack->initTransaction($price, $currencyCode, [
                'email'     => $gift->sender_email,
                'reference' => $reference,
                'gift_id'   => $gift->id,
            ]);

            $gift->update(['transaction_reference' => $txn['reference']]);

            return response()->json([
                'provider'          => 'paystack',
                'reference'         => $txn['reference'],
                'authorization_url' => $txn['authorization_url'],
            ]);
        } catch (\Throwable $e) {
            $gift->delete();
            Log::error('Mobile gift payment failed', ['gift' => $data['platform_gift_id'], 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Payment initialisation failed. Please try again.'], 502);
        }
    }

    /**
     * Confirm a card gift with the gateway and pay the owner. Idempotent.
     */
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'reference'  => ['required', 'string'],
            'session_id' => ['nullable', 'string'],
        ]);

        $gift = Gift::with('platformGift')
            ->where('transaction_reference', $request->reference)
            ->first();

        if (! $gift) {
            return response()->json(['status' => 'not_found'], 404);
        }

        if ($gift->payment_status === 'paid') {
            return response()->json(['status' => 'already_credited']);
        }

        $paid = strtoupper($gift->currency) === 'USD'
            ? $this->stripe->confirmPaidFor($request->session_id, $gift->transaction_reference)
            : $this->paystackPaid($gift->transaction_reference);

        if (! $paid) {
            return response()->json([
                'status'  => 'unconfirmed',
                'message' => 'We could not confirm that payment. If you were charged, contact support and quote '.$gift->transaction_reference.'.',
            ], 422);
        }

        DB::transaction(function () use ($gift) {
            $gift->update(['payment_status' => 'paid']);
            $this->creditOwner($gift, strtoupper($gift->currency) === 'USD' ? 'global' : 'local');
        });

        $this->notifyOwner($gift);

        return response()->json([
            'status'  => 'credited',
            'message' => '🎁 Your gift has been sent successfully!',
        ]);
    }

    private function creditOwner(Gift $gift, string $walletType): void
    {
        $owner = $gift->celebration?->user;

        if (! $owner) {
            return;
        }

        /*
         * Own reference for the receiving leg: wallet_transactions.reference is
         * UNIQUE, so reusing the sender's reference for the owner's credit
         * violates it and rolls the whole gift back. Both legs still point at
         * the same gift via transactionable_type/id.
         */
        $this->wallet->credit(
            user:             $owner,
            amount:           (float) $gift->amount,
            description:      'Gift received: '.($gift->platformGift?->gift_name ?? 'Platform Gift'),
            reference:        $gift->transaction_reference.'-in',
            source:           $gift,
            originalAmount:   (float) $gift->amount,
            originalCurrency: $gift->currency,
            walletType:       $walletType,
        );
    }

    /**
     * Queued, and never allowed to break the response — the money has already
     * moved by the time this runs.
     */
    private function notifyOwner(Gift $gift): void
    {
        $celebration = $gift->celebration;

        if (! $celebration?->user?->email) {
            return;
        }

        try {
            Mail::to($celebration->user->email)->queue(
                new GiftReceivedMail($gift->load('platformGift'), $celebration)
            );
        } catch (\Throwable $e) {
            Log::warning('Gift received mail failed to queue', ['gift' => $gift->id, 'error' => $e->getMessage()]);
        }
    }

    private function paystackPaid(string $reference): bool
    {
        try {
            return ($this->paystack->verifyTransaction($reference)['status'] ?? null) === 'success';
        } catch (\Throwable $e) {
            Log::warning('Mobile Paystack verification failed', ['reference' => $reference, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
