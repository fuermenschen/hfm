<?php

use App\Components\AdminAssociationMemberMessage;
use App\Mail\GenericMailMessage;
use App\Models\AssociationMember;
use App\Models\User;
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
        ->set('selected_members', [$member->id])
        ->set('subject', 'Test Subject')
        ->set('message', 'Test Message')
        ->call('sendMessage');

    Mail::assertQueued(GenericMailMessage::class, function ($mail) use ($member) {
        return $mail->hasTo($member->email);
    });
});

it('sends a message with attachments successfully', function () {
    Mail::fake();

    $member = AssociationMember::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100);

    Livewire::test(AdminAssociationMemberMessage::class)
        ->set('selected_members', [$member->id])
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

// Batch message test
it('sends batch messages to multiple members', function () {
    Mail::fake();

    $members = AssociationMember::factory()->count(3)->create();

    $memberIds = $members->pluck('id')->toArray();

    Livewire::test(AdminAssociationMemberMessage::class)
        ->set('selected_members', $memberIds)
        ->set('subject', 'Batch Subject')
        ->set('message', 'Batch Message')
        ->call('sendMessage');

    foreach ($members as $member) {
        Mail::assertQueued(GenericMailMessage::class, function ($mail) use ($member) {
            return $mail->hasTo($member->email);
        });
    }
});

it('does not send message if no members are selected', function () {
    Mail::fake();

    Livewire::test(AdminAssociationMemberMessage::class)
        ->set('selected_members', [])
        ->set('subject', 'No Members')
        ->set('message', 'No Members')
        ->call('sendMessage')
        ->assertHasErrors('selected_members');

    Mail::assertNothingQueued();
});

it('replaces placeholders in subject and message for each member', function () {
    Mail::fake();

    $members = AssociationMember::factory()
        ->count(2)
        ->sequence(
            ['first_name' => 'Max', 'last_name' => 'Mustermann'],
            ['first_name' => 'Erika', 'last_name' => 'Musterfrau'],
        )
        ->create();

    $memberIds = $members->pluck('id')->toArray();

    Livewire::test(AdminAssociationMemberMessage::class)
        ->set('selected_members', $memberIds)
        ->set('subject', 'Hallo [!first_name] [!last_name]')
        ->set('message', 'Willkommen, [!first_name]!')
        ->call('sendMessage');

    foreach ($members as $member) {
        Mail::assertQueued(GenericMailMessage::class, function ($mail) use ($member) {
            return
                str_contains($mail->subject, $member->first_name) &&
                str_contains($mail->subject, $member->last_name) &&
                str_contains($mail->html, $member->first_name);
        });
    }
});

it('sends a message when called via event', function () {
    Mail::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $member = AssociationMember::factory()->create();

    Livewire::test(AdminAssociationMemberMessage::class)
        ->set('selected_members', [])
        ->dispatch('openMemberMessageModal', member_ids: [$member->id])
        ->set('subject', 'Test Subject')
        ->set('message', 'Test Message')
        ->call('sendMessage');
    Mail::assertQueued(GenericMailMessage::class, function ($mail) use ($member) {
        return $mail->hasTo($member->email);
    });
});
