<?php

use App\Settings\InvoiceSettings;

it('validates valid input according to rules', function () {
    $rules = InvoiceSettings::rules();

    $valid = validator([
        'qr_iban' => 'CH9300762011623852957',
        'qr_show_amount' => true,
        'due_days' => 30,
    ], $rules);

    expect($valid->passes())->toBeTrue();
});

it('fails validation for invalid input according to rules', function () {
    $rules = InvoiceSettings::rules();

    $invalid = validator([
        'qr_iban' => 'CHINVALID',
        'qr_show_amount' => 'yes',
        'due_days' => 0,
    ], $rules);

    expect($invalid->fails())->toBeTrue();
});
