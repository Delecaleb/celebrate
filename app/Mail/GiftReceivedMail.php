<?php

namespace App\Mail;

use App\Models\Celebration;
use App\Models\Gift;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GiftReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $giftCount;
    public float $totalReceived;

    public function __construct(
        public Gift $gift,
        public Celebration $celebration,
    ) {
        $this->gift->loadMissing('platformGift');

        $paidGifts          = $celebration->gifts()->where('payment_status', 'paid')->get();
        $this->giftCount    = $paidGifts->count();
        $this->totalReceived = (float) $paidGifts->sum('amount');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You received a gift for "' . $this->celebration->title . '"! 🎁',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gift-received',
            with: [
                'ownerFirstName' => $this->celebration->user->first_name,
                'gift'           => $this->gift,
                'celebration'    => $this->celebration,
                'giftCount'      => $this->giftCount,
                'totalReceived'  => $this->totalReceived,
            ],
        );
    }
}
