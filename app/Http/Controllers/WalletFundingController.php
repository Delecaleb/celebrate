<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\PaystackService;
use App\Services\PaymentSystem\StripeService;
use App\Services\PaymentSystem\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WalletFundingController extends Controller
{
    public function __construct(
        private CurrencyService $currency,
        private WalletService   $wallet,
        private PaystackService $paystack,
        private StripeService   $stripe,
    ) {}

    // -------------------------------------------------------------------------
    // Initiate wallet funding — creates a pending transaction, routes to gateway
    // -------------------------------------------------------------------------

    public function initiate(Request $request)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $user         = Auth::user();
        $userCurrency = $this->currency->forUser($user);
        $baseCurrency = config('currency.base', 'USD');
        $localAmount  = (float) $request->amount;
        $amountBase   = $userCurrency === $baseCurrency
            ? $localAmount
            : $this->currency->convert($localAmount, $userCurrency, $baseCurrency);
        $reference    = 'wf-' . Str::uuid();

        // Pre-create pending transaction so we can find it on callback
        $tx = WalletTransaction::create([
            'user_id'           => $user->id,
            'type'              => 'credit',
            'amount'            => $amountBase,
            'currency'          => $baseCurrency,
            'original_amount'   => $localAmount,
            'original_currency' => $userCurrency,
            'description'       => 'Wallet top-up',
            'reference'         => $reference,
            'status'            => 'pending',
        ]);

        try {
            if (strtoupper($userCurrency) === 'USD') {
                $checkoutUrl = $this->stripe->createCheckoutSession(
                    amount:     $localAmount,
                    currency:   'USD',
                    successUrl: route('wallet.fund.stripe.success', ['reference' => $reference, 'session_id' => '{CHECKOUT_SESSION_ID}']),
                    cancelUrl:  route('dashboard') . '?cancelled=1#wallet',
                    metadata:   ['wallet_tx_id' => $tx->id, 'reference' => $reference],
                );

                return response()->json([
                    'success'           => true,
                    'provider'          => 'stripe',
                    'authorization_url' => $checkoutUrl,
                ]);
            }

            // NGN (or any non-USD) → Paystack
            $txn = $this->paystack->initTransaction($localAmount, $userCurrency, [
                'email'        => $user->email,
                'reference'    => $reference,
                'wallet_tx_id' => $tx->id,
            ]);

            // Update to Paystack's generated reference (used in callback lookup)
            $tx->update(['reference' => $txn['reference']]);

            return response()->json([
                'success'           => true,
                'provider'          => 'paystack',
                'authorization_url' => $txn['authorization_url'],
            ]);
        } catch (\Throwable $e) {
            $tx->delete();
            Log::error('Wallet funding initiation failed', ['error' => $e->getMessage(), 'user' => $user->id]);

            return response()->json([
                'success' => false,
                'message' => 'Payment initialisation failed. Please try again.',
            ], 502);
        }
    }

    // -------------------------------------------------------------------------
    // Paystack callback — verify and credit the wallet
    // -------------------------------------------------------------------------

    public function paystackCallback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (! $reference) {
            return redirect()->route('dashboard')
                ->with('active_tab', 'wallet')
                ->with('error', 'Invalid payment reference.');
        }

        $secretKey = config('services.paystack.secret');
        $response  = Http::withToken($secretKey)
            ->get("https://api.paystack.co/transaction/verify/{$reference}");

        if (! $response->successful() || $response->json('data.status') !== 'success') {
            Log::warning('Wallet funding Paystack verification failed', [
                'reference' => $reference,
                'response'  => $response->json(),
            ]);

            return redirect()->route('dashboard')
                ->with('active_tab', 'wallet')
                ->with('error', 'Payment could not be verified. Please contact support.');
        }

        $tx = WalletTransaction::where('reference', $reference)
            ->where('type', 'credit')
            ->first();

        if (! $tx) {
            return redirect()->route('dashboard')
                ->with('active_tab', 'wallet')
                ->with('error', 'Transaction record not found.');
        }

        if ($tx->status === 'completed') {
            return redirect()->route('dashboard')
                ->with('active_tab', 'wallet')
                ->with('success', 'Your wallet was already funded.');
        }

        DB::transaction(function () use ($tx) {
            $tx->update(['status' => 'completed']);
            $tx->user->increment('wallet_balance', (float) $tx->amount);
        });

        $symbol  = config("currency.currencies.{$tx->original_currency}.symbol", $tx->original_currency);
        $display = $symbol . number_format((float) $tx->original_amount, 2);

        return redirect()->route('dashboard')
            ->with('active_tab', 'wallet')
            ->with('success', "{$display} added to your wallet successfully!");
    }

    // -------------------------------------------------------------------------
    // Stripe Checkout success callback
    // -------------------------------------------------------------------------

    public function stripeSuccess(Request $request)
    {
        $reference = $request->query('reference');
        $sessionId = $request->query('session_id');

        if (! $reference) {
            return redirect()->route('dashboard')
                ->with('active_tab', 'wallet')
                ->with('error', 'Invalid session reference.');
        }

        $tx = WalletTransaction::where('reference', $reference)
            ->where('type', 'credit')
            ->where('user_id', Auth::id())
            ->first();

        if (! $tx) {
            return redirect()->route('dashboard')
                ->with('active_tab', 'wallet')
                ->with('error', 'Transaction record not found.');
        }

        if ($tx->status === 'completed') {
            return redirect()->route('dashboard')
                ->with('active_tab', 'wallet')
                ->with('success', 'Your wallet was already funded.');
        }

        // Verify session status via Stripe API
        if ($sessionId) {
            try {
                $session = $this->stripe->retrieveCheckoutSession($sessionId);
                if ($session->payment_status !== 'paid') {
                    return redirect()->route('dashboard')
                        ->with('active_tab', 'wallet')
                        ->with('error', 'Payment was not completed.');
                }
            } catch (\Throwable $e) {
                Log::warning('Stripe session retrieval failed', ['error' => $e->getMessage()]);
            }
        }

        DB::transaction(function () use ($tx) {
            $tx->update(['status' => 'completed']);
            $tx->user->increment('wallet_balance', (float) $tx->amount);
        });

        $display = '$' . number_format((float) $tx->original_amount, 2);

        return redirect()->route('dashboard')
            ->with('active_tab', 'wallet')
            ->with('success', "{$display} added to your wallet successfully!");
    }
}
