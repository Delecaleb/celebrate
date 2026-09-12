<?php

namespace App\Console\Commands;

use App\Models\Gift;
use App\Models\WalletTransaction;
use App\Models\WishContribution;
use App\Services\PaymentSystem\PaymentFulfilmentService;
use App\Services\PaymentSystem\PaystackService;
use App\Services\PaymentSystem\StripeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * The safety net under the callbacks and the webhooks.
 *
 * A payment can still be left in limbo — a webhook that never arrived, a
 * gateway outage, a deploy in the wrong second. This asks the gateway about
 * every payment that has been pending for a while and settles it either way,
 * so nobody's money sits in a record marked "pending" forever.
 *
 * Runs every ten minutes (see routes/console.php).
 */
class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile
                            {--minutes=15 : Only look at payments older than this}
                            {--days=7 : Give up on anything older than this}
                            {--dry-run : Report what would happen, change nothing}';

    protected $description = 'Settle payments left pending by a missed callback or webhook';

    public function handle(
        PaymentFulfilmentService $fulfilment,
        PaystackService $paystack,
        StripeService $stripe,
    ): int {
        $olderThan = now()->subMinutes((int) $this->option('minutes'));
        $youngerThan = now()->subDays((int) $this->option('days'));
        $dryRun    = (bool) $this->option('dry-run');

        $settled = 0;
        $failed  = 0;
        $stale   = 0;

        foreach ($this->pending($olderThan, $youngerThan) as $row) {
            [$reference, $label] = $row;

            if ($dryRun) {
                $this->line("would check {$label} {$reference}");
                continue;
            }

            $paid = $this->confirmWithGateway($reference, $paystack, $stripe);

            if ($paid === null) {
                $stale++;   // gateway unreachable — leave it for the next run
                continue;
            }

            if ($paid) {
                $status = $fulfilment->fulfil($reference);
                $settled++;

                $this->info("settled {$label} {$reference} ({$status})");
                Log::info('Reconciliation settled a payment', compact('reference', 'label', 'status'));

                continue;
            }

            $failed++;
            $this->line("still unpaid: {$label} {$reference}");
        }

        $this->newLine();
        $this->info("settled {$settled}, still unpaid {$failed}, could not check {$stale}");

        return self::SUCCESS;
    }

    /**
     * Every payment still waiting, newest first.
     *
     * @return \Generator<int, array{0: string, 1: string}>
     */
    private function pending(\DateTimeInterface $olderThan, \DateTimeInterface $youngerThan): \Generator
    {
        foreach (Gift::where('payment_status', 'pending')
            ->where('payment_method', 'card')
            ->whereBetween('created_at', [$youngerThan, $olderThan])
            ->cursor() as $gift) {
            yield [$gift->transaction_reference, 'gift'];
        }

        foreach (WalletTransaction::where('status', 'pending')
            ->where('type', 'credit')
            ->whereBetween('created_at', [$youngerThan, $olderThan])
            ->cursor() as $tx) {
            yield [$tx->reference, 'wallet top-up'];
        }

        foreach (WishContribution::where('payment_status', 'pending')
            ->whereBetween('created_at', [$youngerThan, $olderThan])
            ->cursor() as $contribution) {
            yield [$contribution->payment_reference, 'wish contribution'];
        }
    }

    /**
     * Did this actually get paid?
     *
     * true / false is the gateway's answer; null means we could not get one,
     * which must never be treated as "unpaid".
     */
    private function confirmWithGateway(?string $reference, PaystackService $paystack, StripeService $stripe): ?bool
    {
        if (! $reference) {
            return false;
        }

        // Stripe references are the ones we created for a global-wallet
        // checkout; everything else went through Paystack.
        try {
            if ($paystack->isConfigured()) {
                $data = $paystack->verifyTransaction($reference);

                if (($data['status'] ?? null) === 'success') {
                    return true;
                }

                // Paystack knows the reference and says it did not succeed.
                if (isset($data['status'])) {
                    return false;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Reconciliation could not reach Paystack', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);

            return null;
        }

        if (! $stripe->isConfigured()) {
            // Nothing else can confirm it, and guessing is not an option.
            return null;
        }

        try {
            // Asked directly rather than through confirmPaidFor, which cannot
            // distinguish "not paid" from "could not ask".
            $session = $stripe->findSessionByReference($reference);

            if (! $session) {
                return false;
            }

            return ($session->payment_status ?? null) === 'paid';
        } catch (\Throwable $e) {
            Log::warning('Reconciliation could not reach Stripe', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);

            return null;
        }
    }
}
