<?php

use App\Models\ExternalUser;

use function Pest\Laravel\actingAs;

it('lets an external user update their address but not their name', function (): void {
    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Francesca',
        'last_name' => 'Arslan',
        'address' => 'Alte Adresse 1',
        'zip_code' => '8406',
        'city' => 'Winterthur',
        'country_of_residence' => 'CH',
        'phone_number' => '+41 79 123 45 67',
    ]);

    actingAs($externalUser, 'external');

    $page = visit(route('portal.profile'));

    $page->assertNoJavaScriptErrors()
        ->assertPresent('input[readonly][value="Francesca"]')
        ->assertPresent('input[readonly][value="Arslan"]')
        ->type('[wire\:model\.live\.blur="address"]', 'Neue Adresse 2')
        ->pressAndWaitFor('Speichern', 0.2)
        ->assertPathIs('/portal')
        ->assertNoJavaScriptErrors();

    $externalUser->refresh();

    expect($externalUser->address)->toBe('Neue Adresse 2');

    $page->navigate(route('portal.profile'))
        ->click('Hilfe & Kontakt')
        ->assertPathIs('/kontakt')
        ->assertSee('Kontakt')
        ->assertNoJavaScriptErrors();
});
