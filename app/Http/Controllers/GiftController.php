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

        if (! $this->wallet->hasSufficientBalance($user, $priceUsd)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient wallet balance.',
                'code'    => 'insufficient_balance',
            ], 422);
        }

        $rate         = $this->currency->getRate(config('currency.base', 'USD'), $userCurrency);
        $displayPrice = $this->currency->convert($priceUsd, config('currency.base', 'USD'), $userCurrency);
        $reference    = 'wallet-' . Str::uuid();

        $gift = Gift::create([
            'celebration_id'        => $request->celebration_id,
            'platform_gift_id'      => $platformGift->id,
            'sender_user_id'        => $user->id,
            'sender_name'           => $user->first_name . ' ' . $user->last_name,
            'sender_email'          => $user->email,
            'amount'                => $priceUsd,
            'currency'              => config('currency.base', 'USD'),
            'guest_currency'        => $userCurrency,
            'conversion_rate'       => $rate,
            'payment_method'        => 'wallet',
            'transaction_reference' => $reference,
            'payment_status'        => 'paid',
            'message'               => $request->message,
            'is_anonymous'          => false,
        ]);

        $this->wallet->debit(
            user:             $user,
            amountBase:       $priceUsd,
            description:      "Gift sent: {$platformGift->gift_name}",
            reference:        $reference,
            source:           $gift,
            originalAmount:   $displayPrice,
            originalCurrency: $userCurrency,
        );

        $newBalanceDisplay = $this->wallet->balance($user, $userCurrency);
        $symbol            = config("currency.currencies.{$userCurrency}.symbol", $userCurrency);

        $gift->load('platformGift');
        $celebration = $gift->celebration()->with('user')->first();
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
        $localPrice   = $this->currency->convert($priceUsd, config('currency.base', 'USD'), $userCurrency);
        $rate         = $this->currency->getRate(config('currency.base', 'USD'), $userCurrency);
        $reference    = 'gift-pay-' . Str::uuid();

        // Pre-create the gift record so the callback can find it
        $gift = Gift::create([
            'celebration_id'        => $request->celebration_id,
            'platform_gift_id'      => $platformGift->id,
            'sender_user_id'        => $user->id,
            'sender_name'           => $user->first_name . ' ' . $user->last_name,
            'sender_email'          => $user->email,
            'amount'                => $priceUsd,
            'currency'              => config('currency.base', 'USD'),
            'guest_currency'        => $userCurrency,
            'conversion_rate'       => $rate,
            'payment_method'        => 'card',
            'transaction_reference' => $reference,
            'payment_status'        => 'pending',
            'message'               => $request->message,
            'is_anonymous'          => false,
        ]);

        try {
            if (strtoupper($userCurrency) === 'USD') {
                $clientSecret = $this->stripe->createPaymentIntent($priceUsd, 'USD', [
                    'gift_id'        => $gift->id,
                    'celebration_id' => $request->celebration_id,
                ]);

                return response()->json([
                    'success'       => true,
                    'provider'      => 'stripe',
                    'client_secret' => $clientSecret,
                    'reference'     => $reference,
                ]);
            }

            $txn = $this->paystack->initTransaction($localPrice, $userCurrency, [
                'email'          => $user->email,
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
            Log::error('Gift payment initiation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Payment initialisation failed. Please try again.',
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

        $celebration = $gift->celebration()->with('user')->first();
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
