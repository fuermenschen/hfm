<?php

use App\Components\AdminAssociationMemberMessage;
use App\Mail\GenericMailMessage;
use App\Models\AssociationMember;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(AdminAssociationMemberMessage::class)
        ->assertStatus(200);
});

it('validates required fields', function () {
    Livewire::test(AdminAssociationMemberMessage::class)
        ->set('subject')
        ->set('message')
        ->call('sendMessage')
        ->assertHasErrors(['subject' => 'required', 'message' => 'required']);
});

it('adds an attachment successfully', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100);

    Livewire::test(AdminAssociationMemberMessage::class)
        ->set('currentAttachment', $file)
        ->call('addAttachment')
        ->assertSet('attachments.0.name', 'document.pdf');
});

it('validates attachment size', function () {
    $file = UploadedFile::fake()->create('document.pdf', 6000);

    Livewire::test(AdminAssociationMemberMessage::class)
        ->set('currentAttachment', $file)
        ->call('addAttachment')
        ->assertHasErrors(['currentAttachment' => 'max']);
});

it('removes an attachment successfully', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100);

    $component = Livewire::test(AdminAssociationMemberMessage::class)
        ->set('currentAttachment', $file)
        ->call('addAttachment');

    $attachmentName = $component->get('attachments')[0]['name'];

    $component->call('removeAttachment', $attachmentName)
        ->assertCount('attachments', 0);
});

it('sends a message successfully', function () {
    Mail::fake();

    $member = AssociationMember::factory()->create();

    Livewire::test(AdminAssociationMemberMessage::class)
        ->set('member', $member)
        ->set('subject', 'Test Subject')
        ->set('message', 'Test Message')
        ->call('sendMessage');

    Mail::assertQueued(GenericMailMessage::class, $member->email);

});

it('sends a message with attachments successfully', function () {
    Mail::fake();

    $member = AssociationMember::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100);

    Livewire::test(AdminAssociationMemberMessage::class)
        ->set('member', $member)
        ->set('subject', 'Test Subject')
        ->set('message', 'Test Message')
        ->set('currentAttachment', $file)
        ->call('addAttachment')
        ->call('sendMessage');

    Mail::assertQueued(GenericMailMessage::class, function (GenericMailMessage $mail) use ($member, $file) {
        return $mail->hasTo($member->email) &&
            collect($mail->diskAttachments)->contains(function ($attachment) use ($file) {
                return $attachment['name'] === $file->getClientOriginalName();
            });
    });

});

it('handles unknown member gracefully', function () {
    Livewire::test(AdminAssociationMemberMessage::class)
        ->call('openAndPrepareSendMailModal', 999)
        ->assertSet('member', null);
});
