<?php

use App\Mail\DonorInvoiceMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Attachment;

it('creates Attachment instances from disk attachments', function () {
    $mail = new DonorInvoiceMail(
        subject: 'Test',
        body: 'Hi',
        storageAttachments: [[
            'disk' => 'local',
            'path' => 'some/path/to/file.pdf',
            'name' => 'file.pdf',
            'mime' => 'application/pdf',
        ]]
    );

    $attachments = $mail->attachments();

    expect($attachments)
        ->toBeArray()
        ->toHaveCount(1);

    expect($attachments[0])->toBeInstanceOf(Attachment::class);
});

it('renders escaped invoice body with line breaks and is queued', function (): void {
    $mail = new DonorInvoiceMail(
        subject: 'Test',
        body: "Hello <script>\n\nSecond line",
    );

    $rendered = $mail->render();

    expect($mail)
        ->toBeInstanceOf(ShouldQueue::class)
        ->and($rendered)->toContain('Hello &lt;script&gt;<br />')
        ->and($rendered)->toContain('Second line');
});
