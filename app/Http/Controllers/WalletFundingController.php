<?php

namespace App\Http\Controllers;

use App\Models\WalletTransaction;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\PaymentFulfilmentService;
use App\Services\PaymentSystem\PaystackService;
use App\Services\PaymentSystem\StripeService;
use App\Services\PaymentSystem\WalletService;
use App\Support\PaymentGateways;
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
        private PaymentFulfilmentService $fulfilment,
    ) {}

    // -------------------------------------------------------------------------
    // Initiate wallet funding â€” creates a pending transaction, routes to gateway
    // -------------------------------------------------------------------------

    public function initiate(Request $request)
    {
        $request->validate([
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'wallet_type' => ['sometimes', 'string', 'in:local,global'],
        ]);

        $user         = Auth::user();
        $userCurrency = $this->currency->forUser($user);
        // A USD user has no local wallet to fund, whatever the form posted.
        $walletType   = $this->wallet->resolveWalletType($user, $request->input('wallet_type', 'local'));
        $amount       = (float) $request->amount;
        $reference    = 'wf-' . Str::uuid();

        $currency = ($walletType === 'global') ? 'USD' : $userCurrency;

        // A gateway an admin has switched off takes no new checkouts. Checked
        // before anything is written, so a refused top-up leaves no pending row.
        if ($refusal = PaymentGateways::refuseCheckout($currency)) {
            return $refusal;
        }

        // Pre-create pending transaction so we can find it on callback
        $tx = WalletTransaction::create([
            'user_id'           => $user->id,
            'type'              => 'credit',
            'wallet_type'       => $walletType,
            'amount'            => $amount,
            'currency'          => $currency,
            'original_amount'   => $amount,
            'original_currency' => $currency,
            'description'       => 'Wallet top-up',
            'reference'         => $reference,
            'status'            => 'pending',
        ]);

        try {
            if ($currency === 'USD') {
                $checkoutUrl = $this->stripe->createCheckoutSession(
                    amount:     $amount,
                    currency:   'USD',
                    successUrl: route('wallet.fund.stripe.success', ['reference' => $reference, 'session_id' => '{CHECKOUT_SESSION_ID}']),
                    cancelUrl:  route('dashboard.wallet') . '?cancelled=1',
                    metadata:   ['wallet_tx_id' => $tx->id, 'reference' => $reference],
                );

                return response()->json([
                    'success'           => true,
                    'provider'          => 'stripe',
                    'authorization_url' => $checkoutUrl,
                ]);
            }

            // NGN (or any non-USD) â†’ Paystack
            $txn = $this->paystack->initTransaction($amount, $currency, [
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
    // Paystack callback â€” verify and credit the wallet
    // -------------------------------------------------------------------------

    public function paystackCallback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (! $reference) {
            return redirect()->route('dashboard.wallet')
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

            return redirect()->route('dashboard.wallet')
                ->with('error', 'Payment could not be verified. Please contact support.');
        }

        $tx = WalletTransaction::where('reference', $reference)
            ->where('type', 'credit')
            ->first();

        if (! $tx) {
            return redirect()->route('dashboard.wallet')
                ->with('error', 'Transaction record not found.');
        }

        // Idempotent: the webhook may have credited this already.
        $status = $this->fulfilment->fulfilWalletFunding($reference);

        if ($status === PaymentFulfilmentService::ALREADY) {
            return redirect()->route('dashboard.wallet')
                ->with('success', 'Your wallet was already funded.');
        }

        $symbol  = config("currency.currencies.{$tx->original_currency}.symbol", $tx->original_currency);
        $display = $symbol . number_format((float) $tx->original_amount, 2);

        return redirect()->route('dashboard.wallet')
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
            return redirect()->route('dashboard.wallet')
                ->with('error', 'Invalid session reference.');
        }

        $tx = WalletTransaction::where('reference', $reference)
            ->where('type', 'credit')
            ->where('user_id', Auth::id())
            ->first();

        if (! $tx) {
            return redirect()->route('dashboard.wallet')
                ->with('error', 'Transaction record not found.');
        }

        if ($tx->status === 'completed') {
            return redirect()->route('dashboard.wallet')
                ->with('success', 'Your wallet was already funded.');
        }

        // The wallet is only credited once Stripe confirms this exact reference.
        // A missing session id or an unreachable Stripe used to be logged and
        // then ignored, crediting the balance for a payment that never happened.
        if (! $this->stripe->confirmPaidFor($sessionId, $reference)) {
            return redirect()->route('dashboard.wallet')
                ->with('error', 'We could not confirm that payment. If you were charged, contact support and quote ' . $reference . '.');
        }

        $status = $this->fulfilment->fulfilWalletFunding($reference);

        if ($status === PaymentFulfilmentService::ALREADY) {
            return redirect()->route('dashboard.wallet')
                ->with('success', 'Your wallet was already funded.');
        }

        $display = '$' . number_format((float) $tx->original_amount, 2);

        return redirect()->route('dashboard.wallet')
            ->with('success', "{$display} added to your wallet successfully!");
    }
}
