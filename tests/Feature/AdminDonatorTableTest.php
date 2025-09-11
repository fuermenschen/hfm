<?php

use App\Components\AdminDonatorTable;
use App\Jobs\CreateDonorInvoice;
use App\Models\Donator;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;

it('dispatches CreateDonorInvoice synchronously from action', function () {
    $donator = Donator::factory()->create();

    Bus::fake();

    Livewire::test(AdminDonatorTable::class)
        ->call('createDonorInvoice', $donator->id)
        ->assertStatus(200);

    Bus::assertDispatchedSync(CreateDonorInvoice::class, function ($job) use ($donator) {
        return $job->donor->is($donator);
    });
});
