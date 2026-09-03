<?php

use App\Components\AdminAssociationDonationInvoiceForm;
use App\Components\AdminNormalizeSvgFiles;
use App\Components\AdminPersonTable;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('renders athlete and donor admin pages for authenticated users', function (): void {
    $user = User::factory()->create();

    actingAs($user);

    get('/admin/sportlerinnen')
        ->assertSuccessful()
        ->assertSee('Sportler:innen')
        ->assertSee('data-flux-icon', false)
        ->assertDontSee('Zahlungsstatus');
    get('/admin/spenderinnen')
        ->assertSuccessful()
        ->assertSee('Spender:innen')
        ->assertSee('Zahlungsstatus');
    get('/admin/externe-personen')->assertNotFound();

    Livewire::test(AdminPersonTable::class, ['role' => 'athlete'])->assertStatus(200);
    Livewire::test(AdminPersonTable::class, ['role' => 'donor'])->assertStatus(200);
});

it('renders tools page with association donation invoice entrypoint', function (): void {
    $user = User::factory()->create();

    actingAs($user);

    get('/admin/tools')
        ->assertSuccessful()
        ->assertSee('Spendenrechnung erstellen')
        ->assertSee('SVGs normalisieren');

    Livewire::test(AdminAssociationDonationInvoiceForm::class)->assertStatus(200);
    Livewire::test(AdminNormalizeSvgFiles::class)->assertSuccessful();
});
