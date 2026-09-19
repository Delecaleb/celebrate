<?php

namespace App\Mail;

use App\Models\Celebration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Two days out: the last comfortable moment to change anything.
 *
 * Deliberately the "get it right" email rather than the countdown's scoreboard —
 * how to edit the details, and how to get the link in front of people while
 * there is still time for them to write something.
 */
class CelebrationTwoDaysMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Celebration $celebration)
    {
        $celebration->loadMissing('user');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Two days to go — let\'s get "' . $this->celebration->title . '" ready! 🎉',
        );
    }

    public function content(): Content
    {
        $checklist = CelebrationChecklist::for($this->celebration);

        return new Content(
            view: 'emails.celebration-two-days',
            with: $checklist + [
                'firstName'   => $this->celebration->user?->first_name,
                'celebration' => $this->celebration,
                'dateLabel'   => $this->celebration->celebrationDate()?->format('l, j F'),
            ],
        );
    }
}
