<?php

use App\Components\AssociationDonationForm;
use App\Notifications\AssociationDonationInvoice;
use Illuminate\Contracts\Queue\ShouldQueue;
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

    Notification::assertSentTo(
        Notification::route('mail', 'john.doe@example.com'),
        AssociationDonationInvoice::class,
        function (AssociationDonationInvoice $notification): bool {
            $pdf = base64_decode($notification->pdfBase64, true);

            return is_string($pdf)
                && str_starts_with($pdf, '%PDF')
                && $notification->filename === 'Spendenrechnung_John_Doe_VereinFuerMenschen.pdf';
        },
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
        AssociationDonationInvoice::class,
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
        AssociationDonationInvoice::class,
    );

});

it('formats and queues association donation invoices', function (): void {
    $notification = new AssociationDonationInvoice(
        firstName: 'John',
        pdfBase64: base64_encode('%PDF-test'),
        filename: 'invoice.pdf',
    );

    $mail = $notification->toMail(new stdClass);

    expect($notification)
        ->toBeInstanceOf(ShouldQueue::class)
        ->and($mail->introLines)->toContain('Im Anhang findest du eine Spendenrechnung.')
        ->and($mail->rawAttachments[0]['options']['mime'])->toBe('application/pdf');
});

it('cannot be submitted empty', function () {
    Livewire::test(AssociationDonationForm::class)
        ->set('first_name', '')
        ->call('submit')
        ->assertHasErrors();
});
