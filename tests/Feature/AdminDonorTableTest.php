<?php

use App\Components\AdminDonorTable;
use App\Jobs\CreateDonorInvoice;
use App\Jobs\DeleteDonorInvoiceDebitor;
use App\Models\Donor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('does not create invoice when debitor and letter_pdf already exist', function () {
    Bus::fake();

    /** @var Donor $donor */
    $donor = Donor::factory()->create([
        'webling_data' => [
            'debitor_id' => 123,
            'letter_pdf' => ['disk' => 'local', 'path' => 'invoices/sample.pdf'],
        ],
    ]);

    Livewire::test(AdminDonorTable::class)
        ->call('createDonorInvoice', $donor->id);

    Bus::assertNotDispatched(CreateDonorInvoice::class);
});

it('does not delete invoice when neither debitor nor letter_pdf exist', function () {
    Bus::fake();

    /** @var Donor $donor */
    $donor = Donor::factory()->create([
        'webling_data' => [],
    ]);

    Livewire::test(AdminDonorTable::class)
        ->call('deleteDonorInvoice', $donor->id);

    Bus::assertNotDispatched(DeleteDonorInvoiceDebitor::class);
});

it('download returns null when no letter_pdf present', function () {
    /** @var Donor $donor */
    $donor = Donor::factory()->create([
        'webling_data' => [],
    ]);

    Livewire::test(AdminDonorTable::class)
        ->call('downloadDonorInvoice', $donor->id)
        ->assertReturned(null)
        ->assertNoFileDownloaded();
});
