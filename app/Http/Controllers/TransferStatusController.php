<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use App\Models\WalletTransaction;
use App\Models\WishContribution;
use App\Services\PaymentSystem\AlatPayService;
use App\Services\PaymentSystem\PaymentFulfilmentService;
use Illuminate\Http\Request;

/**
 * "Has my transfer landed yet?"
 *
 * A card payment finishes in front of the payer; a bank transfer does not. The
 * page sits on the account number and asks this every few seconds until the
 * money is in.
 *
 * Normally the answer is already in our own tables, because AlatPay's webhook
 * got here first. When it did not — a callback URL nobody registered, a request
 * that never arrived — this asks AlatPay directly rather than leaving the payer
 * staring at a page that will never change.
 */
class TransferStatusController extends Controller
{
    public function __construct(
        private PaymentFulfilmentService $fulfilment,
        private AlatPayService $alatpay,
    ) {}

    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'reference'      => ['required', 'string', 'max:120'],
            // AlatPay's own id for the transfer, as handed to this browser when
            // the account was opened.
            'transaction_id' => ['nullable', 'string', 'max:120'],
        ]);

        $reference = $data['reference'];

        if ($this->isPaid($reference)) {
            return response()->json(['paid' => true, 'source' => 'record']);
        }

        // Nothing local says paid. Ask AlatPay, but only about a transfer that
        // was opened for this very reference — a paid transaction id from
        // somewhere else must never settle this one.
        if (! empty($data['transaction_id'])) {
            $remote = $this->alatpay->transactionStatus($data['transaction_id']);

            $belongsHere = $remote !== null
                && (string) ($remote['orderId'] ?? '') === $reference
                && AlatPayService::isPaid((string) ($remote['status'] ?? ''));

            if ($belongsHere) {
                $this->fulfilment->fulfil($reference);

                return response()->json(['paid' => $this->isPaid($reference), 'source' => 'gateway']);
            }
        }

        return response()->json(['paid' => false]);
    }

    /** Whatever this reference paid for, is it settled? */
    private function isPaid(string $reference): bool
    {
        return Gift::where('transaction_reference', $reference)->where('payment_status', 'paid')->exists()
            || WishContribution::where('payment_reference', $reference)->where('payment_status', 'paid')->exists()
            || WalletTransaction::where('reference', $reference)->where('status', 'completed')->exists();
    }
}
