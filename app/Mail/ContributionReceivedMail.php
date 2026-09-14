<?php

namespace App\Mail;

use App\Models\WishContribution;
use App\Support\Outbox;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Telling the celebrant that someone put money towards a registry item.
 *
 * The amount is shown in the currency it was paid in — a ₦5,000 contribution
 * is never presented as dollars — and the running total for the celebration in
 * the celebrant's own currency.
 */
class ContributionReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public WishContribution $contribution)
    {
        $contribution->loadMissing('wish', 'celebration.user');
    }

    /**
     * Queue the email to the celebrant for a contribution that has just been paid.
     *
     * Every place a contribution settles calls this — card and wallet, web and
     * app — so they all send the same mail. Outbox never throws, so the money
     * having already moved is not put at risk by a mail that cannot go.
     */
    public static function notifyCelebrant(WishContribution $contribution): void
    {
        $contribution->loadMissing('wish', 'celebration.user');

        $owner = $contribution->celebration?->user;

        if (! $owner?->email || $contribution->payment_status !== 'paid') {
            return;
        }

        Outbox::queue(
            new self($contribution),
            $owner->email,
            'contribution.received',
            [
                'celebration_id'  => $contribution->celebration_id,
                'contribution_id' => $contribution->id,
                'reference'       => $contribution->payment_reference,
            ],
            $owner->first_name,
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Someone contributed to "' . ($this->contribution->wish?->name ?? 'your registry') . '"! 🎉',
        );
    }

    public function content(): Content
    {
        $contribution = $this->contribution;
        $wish         = $contribution->wish;
        $paidIn       = $contribution->currency ?: (string) config('currency.base');

        // The item's progress, in the same currency as the contribution so the
        // two figures read together.
        $target = $wish ? (float) $wish->displayAmount($paidIn) : 0.0;
        $raised = $wish ? (float) $wish->raisedIn($paidIn) : 0.0;

        return new Content(
            view: 'emails.contribution-received',
            with: [
                'ownerFirstName' => $contribution->celebration?->user?->first_name,
                'contribution'   => $contribution,
                'wish'           => $wish,
                'celebration'    => $contribution->celebration,
                'from'           => $contribution->is_anonymous
                    ? 'Anonymous'
                    : ($contribution->contributor_name ?: 'A guest'),
                'amount'         => GiftLines::money((float) $contribution->amount, $paidIn),
                'raised'         => $target > 0 ? GiftLines::money($raised, $paidIn) : null,
                'target'         => $target > 0 ? GiftLines::money($target, $paidIn) : null,
                'totals'         => CelebrationTotals::received($contribution->celebration),
            ],
        );
    }
}
