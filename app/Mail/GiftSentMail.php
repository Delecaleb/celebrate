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
 * The giver's receipt.
 *
 * Most people who send a gift here have no account — they arrived from a link,
 * paid, and left. This mail is the only record they keep of it, so it carries
 * the reference and the amount in the currency they were actually charged,
 * not a figure converted into something they never saw.
 *
 * One basket, one receipt: several gifts paid for together are lines on this
 * mail rather than a mail each.
 */
class GiftSentMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var Collection<int, Gift> */
    public Collection $gifts;

    public function __construct(
        Gift|Collection $gifts,
        public Celebration $celebration,
    ) {
        $this->gifts = GiftLines::normalise($gifts);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your gift' . ($this->gifts->count() > 1 ? 's' : '')
                . ' to ' . $this->celebration->celebrant_name . ' ' . ($this->gifts->count() > 1 ? 'are' : 'is')
                . ' on the way 🎁',
        );
    }

    public function content(): Content
    {
        $first = $this->gifts->first();

        return new Content(
            view: 'emails.gift-sent',
            with: [
                'senderFirstName' => GiftLines::firstName($first?->sender_name),
                'gift'            => $first,
                'gifts'           => $this->gifts,
                'celebration'     => $this->celebration,
                'lines'           => GiftLines::lines($this->gifts),
                'amount'          => GiftLines::total($this->gifts),
            ],
        );
    }
}
