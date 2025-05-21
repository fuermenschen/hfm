<?php

use App\Components\AssociationGeneralMeetingRegistrationForm;
use App\Mail\GenericMailMessage;
use App\Models\AssociationMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders successfully', function () {
    Livewire::test(AssociationGeneralMeetingRegistrationForm::class)
        ->assertStatus(200);
});

it('registers successfully with valid member', function () {
    Mail::fake();

    $member = AssociationMember::factory()->create([
        'first_name' => 'Anna',
        'last_name' => 'Muster',
        'email' => 'anna@example.com',
    ]);

    Livewire::test(AssociationGeneralMeetingRegistrationForm::class)
        ->set('first_name', 'Anna')
        ->set('last_name', 'Muster')
        ->set('email', 'anna@example.com')
        ->call('submitRegistration')
        ->assertHasNoErrors();

    Mail::assertQueued(GenericMailMessage::class, function ($mail) use ($member) {
        return $mail->hasTo($member->email);
    });
});

it('shows validation errors for missing fields', function () {
    Livewire::test(AssociationGeneralMeetingRegistrationForm::class)
        ->call('submitRegistration')
        ->assertHasErrors(['first_name', 'last_name', 'email']);
});

it('shows error if member not found', function () {

    Mail::fake();

    Livewire::test(AssociationGeneralMeetingRegistrationForm::class)
        ->set('first_name', 'Max')
        ->set('last_name', 'Mustermann')
        ->set('email', 'max@example.com')
        ->call('submitRegistration')
        ->assertHasNoErrors();

    // Check that no email was sent
    Mail::assertNothingQueued();
});
