<?php

use App\Services\Webling\Letter\Dto\QrInvoiceOptions;
use App\Settings\InvoiceSettings;

it('falls back to InvoiceSettings for iban, withAmount and creditor fields when not provided', function (): void {
    // Bind a simple object with the expected properties to the container
    app()->instance(InvoiceSettings::class, new class
    {
        public string $qr_iban = 'CH9300762011623852957';

        public bool $qr_show_amount = true;

        public string $creditor_name = 'Helfende Hände';

        public string $creditor_address1 = 'Bahnhofstrasse 1';

        public string $creditor_address2 = '8000 Zürich';
    });

    $opts = new QrInvoiceOptions;

    $asArray = $opts->toArray();

    expect($asArray['iban'])->toBe('CH9300762011623852957');
    expect($asArray['withAmount'])->toBeTrue();
    expect($asArray['creditorName'])->toBe('Helfende Hände');
    expect($asArray['creditorAddress1'])->toBe('Bahnhofstrasse 1');
    expect($asArray['creditorAddress2'])->toBe('8000 Zürich');
});

it('prefers explicitly provided values over settings where applicable', function (): void {
    // Settings suggest different values
    app()->instance(InvoiceSettings::class, new class
    {
        public string $qr_iban = 'CH9300762011623852957';

        public bool $qr_show_amount = false;

        public string $creditor_name = 'Foo Verein';

        public string $creditor_address1 = 'Barstrasse 99';

        public string $creditor_address2 = '9999 Baz';
    });

    $opts = new QrInvoiceOptions(
        iban: 'CH5604835012345678009',
        withAmount: true,
        creditorName: 'Mein Verein',
        creditorAddress1: 'Hauptstrasse 10',
        creditorAddress2: '3000 Bern',
    );

    $asArray = $opts->toArray();

    expect($asArray['iban'])->toBe('CH5604835012345678009');
    expect($asArray['withAmount'])->toBeTrue();
    expect($asArray['creditorName'])->toBe('Mein Verein');
    expect($asArray['creditorAddress1'])->toBe('Hauptstrasse 10');
    expect($asArray['creditorAddress2'])->toBe('3000 Bern');
});
