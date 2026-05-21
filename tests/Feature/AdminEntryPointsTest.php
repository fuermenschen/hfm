<?php

use App\Components\AdminAssociationDonationInvoiceForm;
use App\Components\AdminExternalUserTable;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('renders external users admin page and datatable for authenticated users', function (): void {
    $user = User::factory()->create();

    actingAs($user);

    get('/admin/externe-personen')
        ->assertSuccessful();

    Livewire::test(AdminExternalUserTable::class)->assertStatus(200);
});

it('renders tools page with association donation invoice entrypoint', function (): void {
    $user = User::factory()->create();

    actingAs($user);

    get('/admin/tools')
        ->assertSuccessful()
        ->assertSee('Spendenrechnung erstellen');

    Livewire::test(AdminAssociationDonationInvoiceForm::class)->assertStatus(200);
});
