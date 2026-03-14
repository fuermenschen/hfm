<?php

use App\Actions\CreateAssociationDonationInvoiceAction;

it('creates a donation invoice payload', function () {
    $invoice = app(CreateAssociationDonationInvoiceAction::class)(
        first_name: 'John',
        last_name: 'Doe',
        address: 'Sample Street 1',
        zip_code: 8000,
        city: 'Zurich',
        company_name: 'Example AG',
        amount: 50.0,
    );

    expect($invoice)
        ->toBeArray()
        ->toHaveKeys(['pdf', 'filename']);

    expect($invoice['filename'])->toBe('Spendenrechnung_John_Doe_VereinFuerMenschen.pdf');
    expect($invoice['pdf']->output())->toStartWith('%PDF');
});

it('creates a donation invoice payload without optional fields', function () {
    $invoice = app(CreateAssociationDonationInvoiceAction::class)(
        first_name: 'Jane',
        last_name: 'Smith',
        address: 'Main Road 10',
        zip_code: 3000,
        city: 'Bern',
    );

    expect($invoice['filename'])->toBe('Spendenrechnung_Jane_Smith_VereinFuerMenschen.pdf');
    expect($invoice['pdf']->output())->toStartWith('%PDF');
});
