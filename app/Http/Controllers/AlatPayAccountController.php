<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\WalletTransaction;
use App\Models\WishContribution;
use App\Services\PaymentSystem\AlatPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * An account number to transfer to, for a browser that cannot open AlatPay's
 * checkout.
 *
 * Their script is blocked often enough to matter — an ad blocker, a corporate
 * network, a phone browser in a bad mood — and without this a payer in that
 * position simply cannot pay. So the same pending payment gets a virtual
 * account instead, and settles through the same webhook.
 *
 * Nothing here is taken from the request except the reference, and only a
 * payment that is still pending gets an account: this cannot be used to open
 * accounts for amounts of somebody's choosing.
 */
class AlatPayAccountController extends Controller
{
    public function __construct(private AlatPayService $alatpay) {}

    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:120'],
        ]);

        $payment = $this->pendingPayment($data['reference']);

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'That payment is no longer waiting to be paid.',
            ], 404);
        }

        try {
            $account = $this->alatpay->createVirtualAccount(
                amount:      $payment['amount'],
                currency:    $payment['currency'],
                orderId:     $data['reference'],
                customer:    ['email' => $payment['email'], 'first_name' => $payment['name']],
                description: $payment['description'],
            );
        } catch (\Throwable $e) {
            Log::error('AlatPay virtual account failed', ['reference' => $data['reference'], 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'We could not open an account for this payment. Please try again.',
            ], 502);
        }

        return response()->json(['success' => true] + $account);
    }

    /**
     * What this reference is waiting to be paid for — whichever table holds it.
     *
     * @return array{amount: float, currency: string, email: string, name: string, description: string}|null
     */
    private function pendingPayment(string $reference): ?array
    {
        $gifts = Gift::where('transaction_reference', $reference)->where('payment_status', 'pending')->get();

        if ($gifts->isNotEmpty()) {
            $first = $gifts->first();

            return [
                // One basket, one payment: the whole thing is transferred at once.
                'amount'      => (float) $gifts->sum('amount'),
                'currency'    => (string) $first->currency,
                'email'       => (string) ($first->sender_email ?: 'guest@celebratemi.com'),
                'name'        => (string) ($first->sender_name ?: 'Guest'),
                'description' => 'Gift for ' . ($first->celebration->celebrant_name ?? 'a celebration'),
            ];
        }

        $contribution = WishContribution::where('payment_reference', $reference)->where('payment_status', 'pending')->first();

        if ($contribution) {
            return [
                'amount'      => (float) $contribution->amount,
                'currency'    => (string) ($contribution->currency ?: config('currency.base')),
                'email'       => (string) ($contribution->contributor_email ?: 'guest@celebratemi.com'),
                'name'        => (string) ($contribution->contributor_name ?: 'Guest'),
                'description' => 'Towards ' . ($contribution->wish->name ?? 'a registry item'),
            ];
        }

        $funding = WalletTransaction::where('reference', $reference)->where('status', 'pending')->first();

        if ($funding) {
            return [
                'amount'      => (float) $funding->amount,
                'currency'    => (string) $funding->currency,
                'email'       => (string) ($funding->user->email ?? 'guest@celebratemi.com'),
                'name'        => (string) ($funding->user->first_name ?? 'Member'),
                'description' => 'Wallet top-up',
            ];
        }

        return null;
    }
}
