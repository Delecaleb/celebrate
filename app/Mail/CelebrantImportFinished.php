<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * What a bulk celebrant upload actually did.
 *
 * The controller has referenced this class since the import feature was
 * written, but it was never created — so ticking "email me the result" threw
 * a fatal instead of sending anything.
 */
class CelebrantImportFinished extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array<int, string>  $errors */
    public function __construct(
        public int $processed,
        public array $errors = [],
    ) {}

    public function envelope(): Envelope
    {
        $failed = count($this->errors);

        return new Envelope(
            subject: $failed === 0
                ? "Your celebrant list is in — {$this->processed} added"
                : "Your celebrant list is in — {$this->processed} added, {$failed} skipped",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.celebrant-import-finished',
            with: [
                'processed' => $this->processed,
                'errors'    => $this->errors,
            ],
        );
    }
}
