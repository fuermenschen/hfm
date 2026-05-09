<?php

declare(strict_types=1);

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
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  string  $subject  The subject of the email.
     * @param  string  $html  The HTML content of the email.
     * @param  array  $storageAttachments  An array of attachments sourced from storage disks.
     */
    /**
     * @param  array<int, array<string, mixed>>  $storageAttachments
     */
    public function __construct(
        string $subject,
        string $html,
        public array $storageAttachments = [],
    ) {
        // Assign to Mailable base properties (untyped in parent)
        $this->subject = $subject;
        $this->html = $html;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
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
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        // Each disk attachment should be an associative array with keys:
        // - disk: storage disk name
        // - path: path within the disk
        // Optional:
        // - name: desired filename presented to the recipient
        // - mime: mime type
        if ($this->storageAttachments === []) {
            return [];
        }

        $attachments = [];
        foreach ($this->storageAttachments as $att) {
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
