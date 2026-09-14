<?php

namespace App\Services\PaymentSystem;

use App\Mail\ContributionReceivedMail;
use App\Mail\GiftReceivedMail;
use App\Mail\GiftSentMail;
use App\Support\Outbox;
use App\Models\Gift;
use App\Models\WalletTransaction;
use App\Models\WishContribution;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Turning a confirmed payment into the thing it paid for.
 *
 * Three routes lead here — the browser coming back to a callback, a gateway
 * webhook, and the reconciliation sweep — so this has to be safe to call twice
 * for the same reference. Every method takes a row lock, re-checks the status
 * inside the transaction and returns ALREADY when there is nothing left to do.
 *
 * Nothing in here verifies the payment: the caller must have confirmed it with
 * the gateway first. This class only writes.
 */
class PaymentFulfilmentService
{
    public const DONE    = 'fulfilled';
    public const ALREADY = 'already-fulfilled';
    public const MISSING = 'not-found';

    public function __construct(
        private WalletService   $wallet,
        private CurrencyService $currency,
    ) {}

    /**
     * Fulfil whatever this reference belongs to.
     *
     * References carry their own type — 'gift-pay-…', 'wf-…', 'wish-pay-…' —
     * which is what lets a single webhook endpoint serve all three flows.
     */
    public function fulfil(string $reference): string
    {
        return match (true) {
            str_starts_with($reference, 'wish-') => $this->fulfilWishContribution($reference),
            str_starts_with($reference, 'wf-')   => $this->fulfilWalletFunding($reference),
            str_starts_with($reference, 'gift-') => $this->fulfilGift($reference),

            // Paystack sometimes hands back its own reference rather than ours,
            // so fall back to asking each table in turn.
            default => $this->fulfilBySearch($reference),
        };
    }

    public function fulfilGift(string $reference): string
    {
        $outcome = DB::transaction(function () use ($reference) {
            // A reference can carry several gifts: one payment, one basket.
            // They settle together or not at all.
            $gifts = Gift::where('transaction_reference', $reference)
                ->lockForUpdate()
                ->get();

            if ($gifts->isEmpty()) {
                return [self::MISSING, null];
            }

            $unpaid = $gifts->where('payment_status', '!=', 'paid');

            if ($unpaid->isEmpty()) {
                return [self::ALREADY, null];
            }

            $celebration = $unpaid->first()->celebration()->with('user')->first();

            foreach ($unpaid as $gift) {
                $gift->update(['payment_status' => 'paid']);

                if (! $celebration?->user) {
                    continue;
                }

                $this->wallet->credit(
                    user:             $celebration->user,
                    amount:           (float) $gift->amount,
                    description:      'Gift received: ' . $gift->label(),
                    // One credit per gift keeps the ledger itemised, and
                    // wallet_transactions.reference is unique, so each needs
                    // its own.
                    reference:        $reference . '-in-' . $gift->id,
                    source:           $gift,
                    originalAmount:   (float) $gift->amount,
                    originalCurrency: $gift->currency,
                    walletType:       $this->walletTypeFor($gift->currency),
                );
            }

            return [self::DONE, $unpaid->values()];
        });

        [$status, $gifts] = $outcome;

        // Mail goes out after the commit — a queued job must never be able to
        // read a row the transaction has not written yet.
        if ($status === self::DONE && $gifts && $gifts->isNotEmpty()) {
            $first       = $gifts->first();
            $celebration = $first->celebration()->with('user')->first();

            $gifts->each->load('platformGift');

            // One mail for the basket, not one per line.
            if ($celebration?->user?->email) {
                Outbox::queue(
                    new GiftReceivedMail($gifts, $celebration),
                    $celebration->user->email,
                    'gift.received',
                    [
                        'celebration_id' => $celebration->id,
                        'reference'      => $reference,
                        'gift_ids'       => $gifts->pluck('id')->all(),
                    ],
                    $celebration->user->first_name,
                );
            }

            // The giver's receipt. Most of them have no account, so this mail
            // is the only record they keep of the payment. A bad address must
            // not take the fulfilment down with it — the money has already
            // moved by this point.
            if ($celebration) {
                // Outbox never throws and skips an unusable address itself, so
                // the money having already moved is not at risk here.
                Outbox::queue(
                    new GiftSentMail($gifts, $celebration),
                    (string) $first->sender_email,
                    'gift.sent',
                    [
                        'celebration_id' => $celebration->id,
                        'reference'      => $reference,
                        'gift_ids'       => $gifts->pluck('id')->all(),
                    ],
                    $first->sender_name,
                );
            }

            Log::info('Gift fulfilled', [
                'reference' => $reference,
                'gift_ids'  => $gifts->pluck('id')->all(),
            ]);
        }

        return $status;
    }

    public function fulfilWalletFunding(string $reference): string
    {
        return DB::transaction(function () use ($reference) {
            $tx = WalletTransaction::where('reference', $reference)
                ->where('type', 'credit')
                ->lockForUpdate()
                ->first();

            if (! $tx) {
                return self::MISSING;
            }

            if ($tx->status === 'completed') {
                return self::ALREADY;
            }

            $tx->update(['status' => 'completed']);
            $tx->user->increment(
                $this->wallet->balanceColumn($tx->user, $tx->wallet_type),
                (float) $tx->amount
            );

            Log::info('Wallet funding fulfilled', ['reference' => $reference, 'transaction_id' => $tx->id]);

            return self::DONE;
        });
    }

    public function fulfilWishContribution(string $reference): string
    {
        [$status, $contribution] = DB::transaction(function () use ($reference) {
            $contribution = WishContribution::where('payment_reference', $reference)
                ->lockForUpdate()
                ->first();

            if (! $contribution) {
                return [self::MISSING, null];
            }

            if ($contribution->payment_status === 'paid') {
                return [self::ALREADY, null];
            }

            $contribution->update(['payment_status' => 'paid']);

            $wish   = $contribution->wish;
            $amount = (float) $contribution->amount;

            // The wish tracks its goal in its own currency, which is not
            // necessarily the one the contributor paid in.
            if ($wish && strtoupper($contribution->currency) !== strtoupper($wish->currency)) {
                $amount = $this->currency->convert(
                    (float) $contribution->amount,
                    $contribution->currency,
                    $wish->currency
                );
            }

            if ($wish) {
                $wish->increment('current_amount', $amount);
                $wish->increment('contribution_count');
            }

            $owner = $contribution->celebration?->user;

            if ($owner) {
                $this->wallet->credit(
                    user:             $owner,
                    amount:           (float) $contribution->amount,
                    description:      'Wish contribution: ' . ($wish->name ?? 'Wish'),
                    reference:        $contribution->payment_reference,
                    source:           $contribution,
                    originalAmount:   (float) $contribution->amount,
                    originalCurrency: $contribution->currency,
                    walletType:       $this->walletTypeFor($contribution->currency),
                );
            }

            Log::info('Wish contribution fulfilled', [
                'reference'       => $reference,
                'contribution_id' => $contribution->id,
            ]);

            return [self::DONE, $contribution];
        });

        // After the commit, as for gifts. Only the call that actually settled
        // it gets here, so a webhook and a callback racing send one mail.
        if ($status === self::DONE && $contribution) {
            ContributionReceivedMail::notifyCelebrant($contribution);
        }

        return $status;
    }

    /**
     * Which wallet a payment lands in.
     *
     * USD is checked out through Stripe and settles in the global wallet;
     * everything else went through Paystack in a local currency. Deriving it
     * from the currency rather than the gateway keeps the two callbacks, the
     * webhook and the reconciliation sweep in agreement.
     */
    private function walletTypeFor(?string $currency): string
    {
        return strtoupper((string) $currency) === 'USD' ? 'global' : 'local';
    }

    /**
     * Last resort when a reference does not carry a recognisable prefix.
     */
    private function fulfilBySearch(string $reference): string
    {
        foreach (['fulfilGift', 'fulfilWalletFunding', 'fulfilWishContribution'] as $method) {
            $status = $this->{$method}($reference);

            if ($status !== self::MISSING) {
                return $status;
            }
        }

        return self::MISSING;
    }
}
