<?php

use App\Services\Webling\Letter\Dto\QrInvoiceOptions;
use App\Settings\InvoiceSettings;

it('falls back to InvoiceSettings for iban, withAmount and creditor fields when not provided', function (): void {
    app()->instance(InvoiceSettings::class, new class
    {
        public string $qr_iban = 'CH9300762011623852957';

        public bool $qr_show_amount = true;

        public string $creditor_name = 'Helfende Hände';

        public string $creditor_street = 'Bahnhofstrasse';

        public string $creditor_building_number = '1';

        public string $creditor_postal_code = '8000';

        public string $creditor_city = 'Zürich';
    });

    $opts = new QrInvoiceOptions;

    $asArray = $opts->toArray();

    expect($asArray['iban'])->toBe('CH9300762011623852957');
    expect($asArray['withAmount'])->toBeTrue();
    expect($asArray['creditorName'])->toBe('Helfende Hände');
    expect($asArray['creditorStreet'])->toBe('Bahnhofstrasse');
    expect($asArray['creditorBuildingNumber'])->toBe('1');
    expect($asArray['creditorPostalCode'])->toBe('8000');
    expect($asArray['creditorCity'])->toBe('Zürich');
    expect($asArray)->not->toHaveKeys(['creditorAddress1', 'creditorAddress2', 'debtorAddress1', 'debtorAddress2']);
});

it('prefers explicitly provided values over settings where applicable', function (): void {
    app()->instance(InvoiceSettings::class, new class
    {
        public string $qr_iban = 'CH9300762011623852957';

        public bool $qr_show_amount = false;

        public string $creditor_name = 'Foo Verein';

        public string $creditor_street = 'Barstrasse';

        public string $creditor_building_number = '99';

        public string $creditor_postal_code = '9999';

        public string $creditor_city = 'Baz';
    });

    $opts = new QrInvoiceOptions(
        iban: 'CH5604835012345678009',
        withAmount: true,
        creditorName: 'Mein Verein',
        creditorStreet: 'Hauptstrasse',
        creditorBuildingNumber: '10',
        creditorPostalCode: '3000',
        creditorCity: 'Bern',
    );

    $asArray = $opts->toArray();

    expect($asArray['iban'])->toBe('CH5604835012345678009');
    expect($asArray['withAmount'])->toBeTrue();
    expect($asArray['creditorName'])->toBe('Mein Verein');
    expect($asArray['creditorStreet'])->toBe('Hauptstrasse');
    expect($asArray['creditorBuildingNumber'])->toBe('10');
    expect($asArray['creditorPostalCode'])->toBe('3000');
    expect($asArray['creditorCity'])->toBe('Bern');
});

it('uses the structured debtor address fields', function (): void {
    app()->instance(InvoiceSettings::class, new class
    {
        public string $qr_iban = 'CH9300762011623852957';

        public bool $qr_show_amount = false;

        public string $creditor_name = '';

        public string $creditor_street = '';

        public string $creditor_building_number = '';

        public string $creditor_postal_code = '';

        public string $creditor_city = '';
    });

    $opts = new QrInvoiceOptions(
        debtorName: ['Clara Klein'],
        debtorStreet: ['Musterweg 5'],
        debtorBuildingNumber: [],
        debtorPostalCode: ['8001'],
        debtorCity: ['Zürich'],
    );

    $asArray = $opts->toArray();

    expect($asArray['debtorName'])->toBe(['Clara Klein']);
    expect($asArray['debtorStreet'])->toBe(['Musterweg 5']);
    expect($asArray['debtorBuildingNumber'])->toBe([]);
    expect($asArray['debtorPostalCode'])->toBe(['8001']);
    expect($asArray['debtorCity'])->toBe(['Zürich']);
});
