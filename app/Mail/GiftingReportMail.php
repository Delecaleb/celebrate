<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class GiftingReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Collection $celebrationBreakdown,
        public Collection $topGifters,
        public float $totalReceived,
        public int $totalGiftCount,
        public int $celebrationCount,
        public string $periodLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ' . $this->periodLabel . ' Gifting Report — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gifting-report',
            with: [
                'firstName'            => $this->user->first_name,
                'celebrationBreakdown' => $this->celebrationBreakdown,
                'topGifters'           => $this->topGifters,
                'totalReceived'        => $this->totalReceived,
                'totalGiftCount'       => $this->totalGiftCount,
                'celebrationCount'     => $this->celebrationCount,
                'periodLabel'          => $this->periodLabel,
            ],
        );
    }
}
