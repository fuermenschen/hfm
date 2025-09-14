<?php

use App\Mail\GenericMailMessage;
use Illuminate\Mail\Mailables\Attachment;

it('creates Attachment instances from disk attachments', function () {
    $mail = new GenericMailMessage(
        subject: 'Test',
        html: '<p>Hi</p>',
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
