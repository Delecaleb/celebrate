<?php

namespace App\Mail;

use App\Models\Celebration;
use App\Models\Gift;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Telling the celebrant that money arrived.
 *
 * Several gifts paid for in one go are lines on one mail — somebody sending a
 * cake, a rose and two cupcake boxes together should not set off four emails.
 */
class GiftReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var Collection<int, Gift> */
    public Collection $gifts;

    public int $giftCount;
    public float $totalReceived;

    public function __construct(
        Gift|Collection $gifts,
        public Celebration $celebration,
    ) {
        $this->gifts = GiftLines::normalise($gifts);

        $paidGifts           = $celebration->gifts()->where('payment_status', 'paid')->get();
        $this->giftCount     = (int) $paidGifts->sum(fn (Gift $gift) => max(1, (int) $gift->quantity));
        $this->totalReceived = (float) $paidGifts->sum('amount');
    }

    public function envelope(): Envelope
    {
        $many = $this->gifts->count() > 1;

        return new Envelope(
            subject: $many
                ? 'You received ' . $this->gifts->count() . ' gifts for "' . $this->celebration->title . '"! 🎁'
                : 'You received a gift for "' . $this->celebration->title . '"! 🎁',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gift-received',
            with: [
                'ownerFirstName' => $this->celebration->user->first_name,
                'gift'           => $this->gifts->first(),
                'gifts'          => $this->gifts,
                'lines'          => GiftLines::lines($this->gifts),
                'basketTotal'    => GiftLines::total($this->gifts),
                'celebration'    => $this->celebration,
                'giftCount'      => $this->giftCount,
                'totalReceived'  => $this->totalReceived,
            ],
        );
    }
}
