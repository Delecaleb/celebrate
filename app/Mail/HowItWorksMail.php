<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HowItWorksMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'How ' . config('app.name') . ' works — your quick guide',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.how-it-works',
            with: [
                'firstName' => $this->user->first_name,
            ],
        );
    }
}
