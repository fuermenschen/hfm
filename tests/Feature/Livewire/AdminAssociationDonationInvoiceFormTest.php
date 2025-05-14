<?php

use App\Components\AdminAssociationDonationInvoiceForm;
use App\Components\BecomeMemberForm;
use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test(AdminAssociationDonationInvoiceForm::class)
        ->assertStatus(200);
});

it('can be filled with all inputs', function () {

    Livewire::test(AdminAssociationDonationInvoiceForm::class)
        ->set('company_name', 'Test Company')
        ->set('first_name', 'John')
        ->set('last_name', 'Doe')
        ->set('address', '123 Test Street')
        ->set('zip_code', 1234)
        ->set('city', 'Test City')
        ->set('amount', 100.50)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertFileDownloaded('Spendenrechnung_John_Doe_VereinFuerMenschen.pdf');

});

it('can be set without a company name', function () {

    Livewire::test(AdminAssociationDonationInvoiceForm::class)
        ->set('company_name', '')
        ->set('first_name', 'Ursula')
        ->set('last_name', 'Müller')
        ->set('address', '123 Test Street')
        ->set('zip_code', 1234)
        ->set('city', 'Test City')
        ->set('amount', 100.50)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertFileDownloaded('Spendenrechnung_Ursula_Müller_VereinFuerMenschen.pdf');

});

it('can be set without an amount', function () {

    Livewire::test(AdminAssociationDonationInvoiceForm::class)
        ->set('company_name', 'Test AG')
        ->set('first_name', 'Fritz')
        ->set('last_name', 'Hugentobler')
        ->set('address', '123 Test Street')
        ->set('zip_code', 1234)
        ->set('city', 'Test City')
        ->set('amount')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertFileDownloaded('Spendenrechnung_Fritz_Hugentobler_VereinFuerMenschen.pdf');

});

it('cannot be submitted empty', function () {
    Livewire::test(BecomeMemberForm::class)
        ->set('first_name', '')
        ->call('submit')
        ->assertHasErrors();
});
