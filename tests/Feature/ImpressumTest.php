<?php

use App\Settings\InvoiceSettings;

use function Pest\Laravel\get;

it('shows the configured creditor address on the impressum page', function () {
    InvoiceSettings::fake([
        'creditor_name' => 'Verein für Menschen',
        'creditor_care_of' => 'Ada Lovelace',
        'creditor_street' => 'Musterweg',
        'creditor_building_number' => '12',
        'creditor_postal_code' => '8000',
        'creditor_city' => 'Zürich',
    ]);

    $response = get(route('impressum'));

    $response->assertSuccessful();
    $response->assertSee('Verein für Menschen')
        ->assertSee('c/o Ada Lovelace')
        ->assertSee('Musterweg 12')
        ->assertSee('8000 Zürich');
});

it('falls back to the hardcoded address when no creditor name is configured', function () {
    InvoiceSettings::fake([
        'creditor_name' => '',
    ]);

    $response = get(route('impressum'));

    $response->assertSuccessful();
    $response->assertSee('Verein für Menschen')
        ->assertSee('c/o Kai Frehner')
        ->assertSee('Rössligasse 6')
        ->assertSee('8405 Winterthur');
});
