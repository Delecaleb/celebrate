<?php

namespace App\Http\Controllers;

use App\Models\Wish;
use App\Models\WishContribution;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\WalletService;
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
        $baseCurrency    = config('currency.base', 'USD');

        $rate      = $this->currency->getRate($baseCurrency, $visitorCurrency);
        $amountUsd = $visitorCurrency === $baseCurrency
            ? $amountDisplay
            : $this->currency->convert($amountDisplay, $visitorCurrency, $baseCurrency);

        if (! $this->wallet->hasSufficientBalance($user, $amountUsd)) {
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
            'contributor_name'    => $user->first_name . ' ' . $user->last_name,
            'contributor_email'   => $user->email,
            'amount'              => $amountUsd,
            'currency'            => $visitorCurrency,
            'conversion_rate'     => $rate,
            'original_amount'     => $amountDisplay,
            'contribution_type'   => 'cash',
            'message'             => $request->message,
            'payment_reference'   => $reference,
            'payment_status'      => 'paid',
            'is_anonymous'        => false,
        ]);

        $this->wallet->debit(
            user:             $user,
            amountBase:       $amountUsd,
            description:      "Wish contribution: {$wish->name}",
            reference:        $reference,
            source:           $contribution,
            originalAmount:   $amountDisplay,
            originalCurrency: $visitorCurrency,
        );

        $wish->increment('current_amount', $amountUsd);
        $wish->increment('contribution_count');

        $newBalanceDisplay = $this->wallet->balance($user, $visitorCurrency);
        $symbol            = config("currency.currencies.{$visitorCurrency}.symbol", $visitorCurrency);

        $fresh          = $wish->fresh();
        $targetDisplay  = $fresh->displayAmount($visitorCurrency);
        $wishRate       = (float) ($fresh->conversion_rate ?? 1);
        $currentDisplay = match (true) {
            $visitorCurrency === $fresh->base_currency                             => (float) $fresh->current_amount,
            $visitorCurrency === $fresh->converted_currency && $wishRate > 0       => round((float) $fresh->current_amount * $wishRate, 2),
            default                                                                => (float) $fresh->current_amount,
        };

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
        $baseCurrency    = config('currency.base', 'USD');

        $rate      = $this->currency->getRate($baseCurrency, $visitorCurrency);
        $amountUsd = $visitorCurrency === $baseCurrency
            ? $amountDisplay
            : $this->currency->convert($amountDisplay, $visitorCurrency, $baseCurrency);

        $reference = 'wish-pay-' . Str::uuid();

        $contribution = WishContribution::create([
            'wish_id'             => $wish->id,
            'celebration_id'      => $wish->celebration_id,
            'contributor_user_id' => $user->id,
            'contributor_name'    => $user->first_name . ' ' . $user->last_name,
            'contributor_email'   => $user->email,
            'amount'              => $amountUsd,
            'currency'            => $visitorCurrency,
            'conversion_rate'     => $rate,
            'original_amount'     => $amountDisplay,
            'contribution_type'   => 'cash',
            'message'             => $request->message,
            'payment_reference'   => $reference,
            'payment_status'      => 'pending',
            'is_anonymous'        => false,
        ]);

        $secretKey = config('services.paystack.secret');
        if (! $secretKey) {
            $contribution->delete();
            return response()->json(['success' => false, 'message' => 'Payment gateway is not configured.'], 503);
        }

        $response = Http::withToken($secretKey)
            ->post('https://api.paystack.co/transaction/initialize', [
                'email'        => $user->email,
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
            $contribution->delete();
            return response()->json([
                'success' => false,
                'message' => $response->json('message', 'Payment initialisation failed.'),
            ], 502);
        }

        return response()->json([
            'success'           => true,
            'authorization_url' => $response->json('data.authorization_url'),
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

        $contribution->update(['payment_status' => 'paid']);

        $contribution->wish->increment('current_amount', $contribution->amount);
        $contribution->wish->increment('contribution_count');

        return redirect()
            ->route('celebrations.show', $contribution->celebration->slug)
            ->with('success', '🎉 Your contribution has been added — thank you!');
    }
}
