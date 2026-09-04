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

class DonorInvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  string  $subject  The subject of the email.
     * @param  string  $body  The plain-text content of the email.
     * @param  array  $storageAttachments  An array of attachments sourced from storage disks.
     */
    /**
     * @param  array<int, array<string, mixed>>  $storageAttachments
     */
    public function __construct(
        string $subject,
        public string $body,
        public array $storageAttachments = [],
    ) {
        $this->subject = $subject;
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
            view: 'mail.donor-invoice',
            text: 'mail.donor-invoice-text',
            with: [
                'body' => $this->body,
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
        foreach ($this->storageAttachments as $attachmentData) {
            $disk = (string) ($attachmentData['disk'] ?? 'local');
            $path = (string) ($attachmentData['path'] ?? '');
            if ($path === '') {
                continue;
            }

            $attachment = Attachment::fromStorageDisk($disk, $path);

            if (! empty($attachmentData['name'])) {
                $attachment = $attachment->as((string) $attachmentData['name']);
            }

            if (! empty($attachmentData['mime'])) {
                $attachment = $attachment->withMime((string) $attachmentData['mime']);
            }

            $attachments[] = $attachment;
        }

        return $attachments;
    }
}
