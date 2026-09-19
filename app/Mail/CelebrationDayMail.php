<?php

namespace App\Mail;

use App\Models\Celebration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The morning of.
 *
 * Nobody wants a lecture today, so this is a short, loud "it's here" — with a
 * setup check only where something is actually missing, and the one thing that
 * matters most: put the link where people are.
 */
class CelebrationDayMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Celebration $celebration)
    {
        $celebration->loadMissing('user');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'It\'s today! 🎊 ' . $this->celebration->title,
        );
    }

    public function content(): Content
    {
        $checklist = CelebrationChecklist::for($this->celebration);

        return new Content(
            view: 'emails.celebration-day',
            with: $checklist + [
                'firstName'   => $this->celebration->user?->first_name,
                'celebration' => $this->celebration,
            ],
        );
    }
}
