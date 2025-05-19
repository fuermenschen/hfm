<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericMailMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  string  $subject  The subject of the email.
     * @param  string  $html  The HTML content of the email.
     * @param  array  $diskAttachments  An array of disk attachments. Default is null.
     */
    public function __construct(
        public $subject,
        public $html,
        public $diskAttachments = [],
    ) {
        $this->subject = (string) $subject;
        $this->html = (string) $html;
        $this->diskAttachments = $diskAttachments;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
            bcc: config('mail.from.address'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.generic',
            with: [
                'bodyHtml' => $this->html,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
