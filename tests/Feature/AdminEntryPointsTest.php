<?php

use App\Components\AdminAssociationDonationInvoiceForm;
use App\Components\AdminExternalUserTable;
use App\Models\User;

it('renders external users admin page and datatable for authenticated users', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/externe-personen')
        ->assertSuccessful()
        ->assertSeeLivewire(AdminExternalUserTable::class);
});

it('renders tools page with association donation invoice entrypoint', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/tools')
        ->assertSuccessful()
        ->assertSee('Spendenrechnung erstellen')
        ->assertSeeLivewire(AdminAssociationDonationInvoiceForm::class);
});
