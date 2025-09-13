<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
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
     * @param  array  $storageAttachments  An array of attachments sourced from storage disks.
     */
    public function __construct(
        public $subject,
        public $html,
        public $storageAttachments = [],
    ) {
        $this->subject = (string) $subject;
        $this->html = (string) $html;
        $this->storageAttachments = $storageAttachments;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $bcc = [];
        $fromAddress = config('mail.from.address');
        if (is_string($fromAddress) && $fromAddress !== '') {
            $bcc = [$fromAddress];
        }

        return new Envelope(
            subject: $this->subject,
            bcc: $bcc,
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
        // Each disk attachment should be an associative array with keys:
        // - disk: storage disk name
        // - path: path within the disk
        // Optional:
        // - name: desired filename presented to the recipient
        // - mime: mime type
        if (! is_array($this->storageAttachments) || empty($this->storageAttachments)) {
            return [];
        }

        $attachments = [];
        foreach ($this->storageAttachments as $att) {
            if (! is_array($att)) {
                continue;
            }
            $disk = (string) ($att['disk'] ?? 'local');
            $path = (string) ($att['path'] ?? '');
            if ($path === '') {
                continue;
            }

            $attachment = Attachment::fromStorageDisk($disk, $path);

            if (! empty($att['name'])) {
                $attachment = $attachment->as((string) $att['name']);
            }
            if (! empty($att['mime'])) {
                $attachment = $attachment->withMime((string) $att['mime']);
            }

            $attachments[] = $attachment;
        }

        return $attachments;
    }
}
