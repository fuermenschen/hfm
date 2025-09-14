<?php

use App\Settings\InvoiceSettings;

it('defines the correct settings group', function () {
    expect(InvoiceSettings::group())->toBe('invoiceSettings');
});

it('exposes settingsDetails, titles and descriptions for invoice settings', function () {
    $details = InvoiceSettings::settingsDetails();
    $titles = InvoiceSettings::titles();
    $descriptions = InvoiceSettings::descriptions();

    expect($details)->toHaveKeys(['title', 'description']);
    expect($titles)->toHaveKeys(['qr_iban', 'qr_show_amount', 'due_days']);
    expect($descriptions)->toHaveKeys(['qr_iban', 'qr_show_amount', 'due_days']);
});
