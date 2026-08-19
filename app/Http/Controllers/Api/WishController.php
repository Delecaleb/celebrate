<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WishResource;
use App\Models\Celebration;
use App\Models\CommentReply;
use App\Models\Wish;
use App\Models\WishContribution;
use App\Services\PaymentSystem\CurrencyService;
use App\Services\PaymentSystem\PaystackService;
use App\Services\PaymentSystem\StripeService;
use App\Services\PaymentSystem\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The registry: the owner's items, and everyone else's contributions to them.
 */
class WishController extends Controller
{
    public function __construct(
        private CurrencyService $currency,
        private WalletService $wallet,
        private PaystackService $paystack,
        private StripeService $stripe,
    ) {}

    /**
     * Add registry items. Accepts a batch, like the web wishlist form.
     */
    public function store(Request $request)
    {
        $request->validate([
            'celebration_id'    => ['required', 'exists:celebrations,id'],
            'wishlist'          => ['required', 'array', 'min:1'],
            'wishlist.*.name'   => ['required', 'string', 'max:255'],
            'wishlist.*.amount' => ['nullable', 'numeric', 'min:0'],
            'wishlist.*.image'  => ['nullable', 'image', 'max:2048'],
            'wishlist.*.description' => ['nullable', 'string', 'max:1000'],
            'wishlist.*.wish_link'   => ['nullable', 'url', 'max:500'],
        ]);

        $celebration = Celebration::findOrFail($request->celebration_id);

        abort_if($celebration->user_id !== $request->user()->id, 403, 'You can only add items to your own registry.');

        $currency = $this->currency->forUser($request->user());
        $created  = [];

        foreach ($request->wishlist as $index => $item) {
            $image = $request->file("wishlist.{$index}.image");
            $raw   = isset($item['amount']) && $item['amount'] !== '' ? (float) $item['amount'] : null;

            // Both branches must carry the same keys, or Wish::create() reads
            // keys that aren't there.
            $amounts = $raw !== null
                ? $this->currency->computeAmounts($raw, $currency)
                : [
                    'currency'           => $currency,
                    'base_currency'      => config('currency.base'),
                    'amount_base'        => null,
                    'converted_currency' => null,
                    'amount_converted'   => null,
                    'conversion_rate'    => null,
                ];

            $created[] = Wish::create([
                'celebration_id' => $celebration->id,
                'name'           => $item['name'],
                'description'    => $item['description'] ?? null,
                'wish_link'      => $item['wish_link'] ?? null,
                'target_amount'  => $raw,
                'wish_image'     => $image ? $image->store('wishlist', 'public') : null,
            ] + $amounts);
        }

        return WishResource::collection(collect($created))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Wish $wish)
    {
        abort_if($wish->celebration->user_id !== $request->user()->id, 403);

        // Soft delete — contribution history outlives the item.
        $wish->delete();

        return response()->json(['message' => 'Registry item removed.']);
    }

    /**
     * Replies on a registry item.
     *
     * comment_replies.wish_id is a foreign key to wishes(id), so this is the
     * only table these can attach to. The web route for posting one points at
     * CelebrationController::storeReply, a method that does not exist, so this
     * is the only working implementation of the feature.
     */
    public function replies(Wish $wish)
    {
        $replies = $wish->replies()->with('user')->oldest()->get()->map(fn ($r) => [
            'id'      => $r->id,
            'message' => $r->message,
            'author'  => $r->user
                ? trim($r->user->first_name.' '.$r->user->last_name)
                : ($r->guest_name ?: 'Guest'),
            'created_at' => $r->created_at?->toIso8601String(),
        ]);

        return response()->json(['data' => $replies]);
    }

    public function reply(Request $request, Wish $wish)
    {
        $data = $request->validate([
            'message'    => ['required', 'string', 'max:1000'],
            'guest_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();

        $reply = CommentReply::create([
            'wish_id'    => $wish->id,
            'user_id'    => $user?->id,
            'guest_name' => $user
                ? trim($user->first_name.' '.$user->last_name)
                : ($data['guest_name'] ?? 'Guest'),
            'message'    => $data['message'],
        ]);

        return response()->json([
            'id'         => $reply->id,
            'message'    => $reply->message,
            'author'     => $reply->guest_name,
            'created_at' => $reply->created_at->toIso8601String(),
        ], 201);
    }

    /**
     * Contribute out of the wallet. Settles immediately, no gateway involved.
     */
    public function contributeFromWallet(Request $request, Wish $wish)
    {
        $data = $request->validate([
            'amount'   => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'message'  => ['nullable', 'string', 'max:500'],
        ]);

        $user       = $request->user();
        $currency   = strtoupper($data['currency']);
        $amount     = (float) $data['amount'];
        $walletType = $currency === 'USD' ? 'global' : 'local';

        if (! $this->wallet->hasSufficientBalance($user, $amount, $walletType)) {
            return response()->json([
                'message' => 'Insufficient wallet balance.',
                'code'    => 'insufficient_balance',
            ], 422);
        }

        $reference = 'wish-wallet-'.Str::uuid();

        DB::transaction(function () use ($wish, $user, $amount, $currency, $data, $reference, $walletType) {
            $contribution = WishContribution::create([
                'wish_id'             => $wish->id,
                'celebration_id'      => $wish->celebration_id,
                'contributor_user_id' => $user->id,
                'contributor_name'    => trim($user->first_name.' '.$user->last_name),
                'contributor_email'   => $user->email,
                'amount'              => $amount,
                'currency'            => $currency,
                'conversion_rate'     => 1.0,
                'original_amount'     => $amount,
                'contribution_type'   => 'cash',
                'message'             => $data['message'] ?? null,
                'payment_reference'   => $reference,
                'payment_status'      => 'paid',
                'is_anonymous'        => false,
            ]);

            $this->wallet->debit(
                user:             $user,
                amount:           $amount,
                description:      "Contribution to {$wish->name}",
                reference:        $reference,
                source:           $contribution,
                originalAmount:   $amount,
                originalCurrency: $currency,
                walletType:       $walletType,
            );

            $this->settle($contribution, $walletType);
        });

        return response()->json([
            'message' => 'Thank you! Your contribution has been recorded.',
            'wish'    => new WishResource($this->withDisplay($wish->fresh(), $currency)),
        ]);
    }

    /**
     * Pay by card. Returns the gateway URL; verify() settles it afterwards.
     */
    public function initiatePayment(Request $request, Wish $wish)
    {
        $user  = $request->user();
        $guest = $user === null;

        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'currency'    => ['required', 'string', 'size:3'],
            'message'     => ['nullable', 'string', 'max:500'],
            'guest_name'  => [$guest ? 'required' : 'nullable', 'string', 'max:120'],
            'guest_email' => [$guest ? 'required' : 'nullable', 'email', 'max:190'],
        ]);

        $currency  = strtoupper($data['currency']);
        $amount    = (float) $data['amount'];
        $reference = 'wish-pay-'.Str::uuid();

        $contribution = WishContribution::create([
            'wish_id'             => $wish->id,
            'celebration_id'      => $wish->celebration_id,
            'contributor_user_id' => $user?->id,
            'contributor_name'    => $guest ? $data['guest_name'] : trim($user->first_name.' '.$user->last_name),
            'contributor_email'   => $guest ? $data['guest_email'] : $user->email,
            'amount'              => $amount,
            'currency'            => $currency,
            'conversion_rate'     => 1.0,
            'original_amount'     => $amount,
            'contribution_type'   => 'cash',
            'message'             => $data['message'] ?? null,
            'payment_reference'   => $reference,
            'payment_status'      => 'pending',
            'is_anonymous'        => false,
        ]);

        $bridge = fn (array $extra = []) => route('payments.bridge', $extra + [
            'reference' => $reference,
            'flow'      => 'wish',
            'slug'      => $wish->celebration->slug,
        ]);

        try {
            if ($currency === 'USD') {
                $url = $this->stripe->createCheckoutSession(
                    amount:     $amount,
                    currency:   'USD',
                    successUrl: $bridge(['session_id' => '{CHECKOUT_SESSION_ID}']),
                    cancelUrl:  $bridge(['cancelled' => 1]),
                    metadata:   ['wish_id' => $wish->id, 'reference' => $reference],
                );

                return response()->json([
                    'provider'          => 'stripe',
                    'reference'         => $reference,
                    'authorization_url' => $url,
                ]);
            }

            $txn = $this->paystack->initTransaction($amount, $currency, [
                'email'     => $contribution->contributor_email,
                'reference' => $reference,
                'wish_id'   => $wish->id,
            ]);

            $contribution->update(['payment_reference' => $txn['reference']]);

            return response()->json([
                'provider'          => 'paystack',
                'reference'         => $txn['reference'],
                'authorization_url' => $txn['authorization_url'],
            ]);
        } catch (\Throwable $e) {
            $contribution->delete();
            Log::error('Mobile wish contribution failed', ['wish' => $wish->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Payment initialisation failed. Please try again.'], 502);
        }
    }

    /**
     * Confirm a card contribution with the gateway and credit the item.
     * Idempotent — the app calls it whenever the payment browser closes.
     */
    public function verifyContribution(Request $request)
    {
        $request->validate([
            'reference'  => ['required', 'string'],
            'session_id' => ['nullable', 'string'],
        ]);

        $contribution = WishContribution::where('payment_reference', $request->reference)->first();

        if (! $contribution) {
            return response()->json(['status' => 'not_found'], 404);
        }

        if ($contribution->payment_status === 'paid') {
            return response()->json(['status' => 'already_credited']);
        }

        $paid = strtoupper($contribution->currency) === 'USD'
            ? $this->stripe->confirmPaidFor($request->session_id, $contribution->payment_reference)
            : $this->paystackPaid($contribution->payment_reference);

        if (! $paid) {
            return response()->json([
                'status'  => 'unconfirmed',
                'message' => 'We could not confirm that payment. If you were charged, contact support and quote '.$contribution->payment_reference.'.',
            ], 422);
        }

        DB::transaction(function () use ($contribution) {
            $contribution->update(['payment_status' => 'paid']);

            // A card payment in USD lands in the owner's global wallet, anything
            // else in their local one — the same split the web callbacks make.
            $this->settle(
                $contribution,
                strtoupper($contribution->currency) === 'USD' ? 'global' : 'local'
            );
        });

        return response()->json([
            'status'  => 'credited',
            'message' => 'Thank you! Your contribution has been recorded.',
            'wish'    => new WishResource(
                $this->withDisplay($contribution->wish->fresh(), $contribution->currency)
            ),
        ]);
    }

    /**
     * Settle a paid contribution: move it onto the item's running total and pay
     * the celebration owner.
     *
     * Both halves matter. current_amount is denominated in the item's own
     * currency, so anything taken in another is converted first; and the owner
     * has to actually receive the money, which is the whole point of the
     * registry. The web controller does exactly this in all three of its paid
     * branches.
     */
    private function settle(WishContribution $contribution, string $walletType): void
    {
        $wish   = $contribution->wish;
        $amount = (float) $contribution->amount;
        $paidIn = $contribution->currency;

        $increment = strtoupper($paidIn) === strtoupper((string) $wish->currency)
            ? $amount
            : $this->currency->convert($amount, $paidIn, $wish->currency);

        $wish->increment('current_amount', $increment);
        $wish->increment('contribution_count');

        /*
         * The owner's credit needs its own reference.
         *
         * wallet_transactions.reference carries a UNIQUE index, so reusing the
         * payer's reference for both legs violates it and the whole contribution
         * rolls back. Both halves still trace to the same contribution through
         * transactionable_type/id, and '-in' keeps the pairing readable.
         */
        $this->wallet->credit(
            user:             $wish->celebration->user,
            amount:           $amount,
            description:      "Wish contribution from {$contribution->contributor_name}: {$wish->name}",
            reference:        $contribution->payment_reference.'-in',
            source:           $contribution,
            originalAmount:   $amount,
            originalCurrency: $paidIn,
            walletType:       $walletType,
        );
    }

    private function withDisplay(Wish $wish, string $currency): Wish
    {
        $wish->displayTarget  = $wish->displayAmount($currency);
        $rate = (float) ($wish->conversion_rate ?? 1);
        $wish->displayCurrent = match (true) {
            $currency === $wish->base_currency                       => (float) $wish->current_amount,
            $currency === $wish->converted_currency && $rate > 0     => round((float) $wish->current_amount * $rate, 2),
            default                                                  => (float) $wish->current_amount,
        };

        return $wish;
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
