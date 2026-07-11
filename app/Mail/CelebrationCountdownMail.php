<?php

namespace App\Mail;

use App\Models\Celebration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CelebrationCountdownMail extends Mailable
{
    use Queueable, SerializesModels;

    public int $giftCount;
    public int $wishCount;
    public int $viewCount;
    public float $totalGifted;

    public function __construct(
        public Celebration $celebration,
        public int $daysUntil,
    ) {
        $this->giftCount  = $celebration->gifts()->where('payment_status', 'paid')->count();
        $this->wishCount  = $celebration->wishes()->count();
        $this->viewCount  = $celebration->view_count ?? 0;
        $this->totalGifted = (float) $celebration->gifts()->where('payment_status', 'paid')->sum('amount');
    }

    public function envelope(): Envelope
    {
        $prefix = $this->daysUntil === 1 ? 'TOMORROW' : "T-{$this->daysUntil}";

        return new Envelope(
            subject: "{$prefix}: \"{$this->celebration->title}\" is almost here! 🎉",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.celebration-countdown',
            with: [
                'firstName'   => $this->celebration->user->first_name,
                'celebration' => $this->celebration,
                'daysUntil'   => $this->daysUntil,
                'giftCount'   => $this->giftCount,
                'wishCount'   => $this->wishCount,
                'viewCount'   => $this->viewCount,
                'totalGifted' => $this->totalGifted,
            ],
        );
    }
}
