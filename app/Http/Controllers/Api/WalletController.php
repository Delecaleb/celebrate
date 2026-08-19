<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletTransactionResource;
use App\Models\WalletTransaction;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\PaystackService;
use App\Services\PaymentSystem\StripeService;
use App\Services\PaymentSystem\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Wallet balances, the ledger, and topping up.
 *
 * Funding follows the same two steps as the web flow — create a pending
 * transaction, send the user to the gateway — but the app cannot rely on a
 * redirect landing back in an authenticated session, so crediting happens in an
 * explicit verify() call the app makes once the browser closes.
 */
class WalletController extends Controller
{
    public function __construct(
        private CurrencyService $currency,
        private WalletService $wallet,
        private PaystackService $paystack,
        private StripeService $stripe,
    ) {}

    public function show(Request $request)
    {
        $user     = $request->user();
        $currency = $this->currency->forUser($user);

        return response()->json([
            'currency' => $currency,
            'symbol'   => config("currency.currencies.{$currency}.symbol", $currency),
            'local'    => round($this->wallet->balance($user, 'local'), 2),
            'global'   => round($this->wallet->balance($user, 'global'), 2),
        ]);
    }

    public function transactions(Request $request)
    {
        return WalletTransactionResource::collection(
            $request->user()->walletTransactions()->latest()->paginate(50)
        );
    }

    /**
     * Start a top-up. Returns the gateway URL for the app to open in a browser.
     */
    public function fund(Request $request)
    {
        $request->validate([
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'wallet_type' => ['sometimes', 'string', 'in:local,global'],
        ]);

        $user       = $request->user();
        $walletType = $request->input('wallet_type', 'local');
        $amount     = (float) $request->amount;
        $reference  = 'wf-'.Str::uuid();
        $currency   = $walletType === 'global' ? 'USD' : $this->currency->forUser($user);

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
                $url = $this->stripe->createCheckoutSession(
                    amount:     $amount,
                    currency:   'USD',
                    successUrl: $this->bridge(['reference' => $reference, 'session_id' => '{CHECKOUT_SESSION_ID}', 'flow' => 'wallet']),
                    cancelUrl:  $this->bridge(['reference' => $reference, 'flow' => 'wallet', 'cancelled' => 1]),
                    metadata:   ['wallet_tx_id' => $tx->id, 'reference' => $reference],
                );

                return response()->json([
                    'provider'          => 'stripe',
                    'reference'         => $reference,
                    'authorization_url' => $url,
                ]);
            }

            $txn = $this->paystack->initTransaction($amount, $currency, [
                'email'        => $user->email,
                'reference'    => $reference,
                'wallet_tx_id' => $tx->id,
            ]);

            // Paystack mints its own reference; the verify call looks up by it.
            $tx->update(['reference' => $txn['reference']]);

            return response()->json([
                'provider'          => 'paystack',
                'reference'         => $txn['reference'],
                'authorization_url' => $txn['authorization_url'],
            ]);
        } catch (\Throwable $e) {
            $tx->delete();
            Log::error('Mobile wallet funding failed', ['user' => $user->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Payment initialisation failed. Please try again.'], 502);
        }
    }

    /**
     * Confirm a top-up with the gateway and credit the wallet.
     *
     * Safe to call more than once — the app calls it whenever the payment
     * browser closes, including when the user simply backed out.
     */
    public function verifyFunding(Request $request)
    {
        $request->validate([
            'reference'  => ['required', 'string'],
            'session_id' => ['nullable', 'string'],
        ]);

        $tx = WalletTransaction::where('reference', $request->reference)
            ->where('type', 'credit')
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $tx) {
            return response()->json(['status' => 'not_found', 'message' => 'Transaction record not found.'], 404);
        }

        if ($tx->status === 'completed') {
            return response()->json([
                'status'  => 'already_credited',
                'message' => 'Your wallet was already funded.',
                'wallet'  => $this->balancesFor($request->user()),
            ]);
        }

        $paid = $tx->currency === 'USD'
            ? $this->stripe->confirmPaidFor($request->session_id, $tx->reference)
            : $this->paystackPaid($tx->reference);

        if (! $paid) {
            return response()->json([
                'status'  => 'unconfirmed',
                'message' => 'We could not confirm that payment. If you were charged, contact support and quote '.$tx->reference.'.',
            ], 422);
        }

        DB::transaction(function () use ($tx) {
            $tx->update(['status' => 'completed']);

            $column = $tx->wallet_type === 'global' ? 'global_wallet_balance' : 'wallet_balance';
            $tx->user->increment($column, (float) $tx->amount);
        });

        $symbol = config("currency.currencies.{$tx->original_currency}.symbol", $tx->original_currency);

        return response()->json([
            'status'  => 'credited',
            'message' => $symbol.number_format((float) $tx->original_amount, 2).' added to your wallet successfully!',
            'wallet'  => $this->balancesFor($request->user()->fresh()),
        ]);
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

    private function balancesFor($user): array
    {
        $currency = $this->currency->forUser($user);

        return [
            'currency' => $currency,
            'symbol'   => config("currency.currencies.{$currency}.symbol", $currency),
            'local'    => round($this->wallet->balance($user, 'local'), 2),
            'global'   => round($this->wallet->balance($user, 'global'), 2),
        ];
    }

    private function bridge(array $params): string
    {
        return route('payments.bridge', $params);
    }
}
