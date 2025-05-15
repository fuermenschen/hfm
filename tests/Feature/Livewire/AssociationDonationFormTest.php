<?php

use App\Components\AssociationDonationForm;
use App\Components\BecomeMemberForm;
use App\Notifications\AssociationDonationMessage;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(AssociationDonationForm::class)
        ->assertStatus(200);
});

it('can be filled with all inputs', function () {

    Notification::fake();

    Livewire::test(AssociationDonationForm::class)
        ->set('company_name', 'Test Company')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('address', '123 Test Street')
        ->set('zip_code', 1234)
        ->set('city', 'Test City')
        ->set('amount', 100.50)
        ->set('email', 'john.doe@example.com')
        ->set('email_confirmation', 'john.doe@example.com')
        ->call('submit')
        ->assertHasNoErrors()
        ->call('redirectHelper')
        ->assertRedirect(route('home'));

    Notification::assertSentToTimes(
        Notification::route('mail', 'john.doe@example.com'),
        AssociationDonationMessage::class,
    );

});

it('can be set without a company name', function () {

    Notification::fake();

    Livewire::test(AssociationDonationForm::class)
        ->set('company_name')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('address', '123 Test Street')
        ->set('zip_code', 1234)
        ->set('city', 'Test City')
        ->set('amount', 100.50)
        ->set('email', 'john.doe@example.com')
        ->set('email_confirmation', 'john.doe@example.com')
        ->call('submit')
        ->assertHasNoErrors()
        ->call('redirectHelper')
        ->assertRedirect(route('home'));

    Notification::assertSentToTimes(
        Notification::route('mail', 'john.doe@example.com'),
        AssociationDonationMessage::class,
    );

});

it('can be set without an amount', function () {

    Notification::fake();

    Livewire::test(AssociationDonationForm::class)
        ->set('company_name', 'Test Company')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('address', '123 Test Street')
        ->set('zip_code', 1234)
        ->set('city', 'Test City')
        ->set('amount')
        ->set('email', 'john.doe@example.com')
        ->set('email_confirmation', 'john.doe@example.com')
        ->call('submit')
        ->assertHasNoErrors()
        ->call('redirectHelper')
        ->assertRedirect(route('home'));

    Notification::assertSentToTimes(
        Notification::route('mail', 'john.doe@example.com'),
        AssociationDonationMessage::class,
    );

});

it('cannot be submitted empty', function () {
    Livewire::test(BecomeMemberForm::class)
        ->set('first_name', '')
        ->call('submit')
        ->assertHasErrors();
});
