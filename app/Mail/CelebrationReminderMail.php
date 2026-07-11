<?php

namespace App\Mail;

use App\Models\Celebration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CelebrationReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Celebration $celebration,
        public string $recipientName,
        public string $recipientEmail,
        public int $daysUntil,
        public bool $isOwner = false,
    ) {}

    public function envelope(): Envelope
    {
        $days  = $this->daysUntil;
        $label = $days === 1 ? 'tomorrow' : "in {$days} days";

        return new Envelope(
            subject: 'Reminder: "' . $this->celebration->title . '" is ' . $label . '!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.celebration-reminder',
            with: [
                'celebration'   => $this->celebration,
                'recipientName' => $this->recipientName,
                'daysUntil'     => $this->daysUntil,
                'isOwner'       => $this->isOwner,
            ],
        );
    }
}
