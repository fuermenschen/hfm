<?php

use App\Components\AdminDonorTable;
use App\Jobs\CreateDonorInvoice;
use App\Models\Athlete;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Partner;
use App\Models\SportType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('skips creating invoices in bulk for donors without donations', function () {
    // Prepare related models
    $sport = SportType::query()->create(['name' => 'Run']);
    $partner = Partner::query()->create(['name' => 'Partner']);
    $athlete = Athlete::factory()->verified()->create([
        'sport_type_id' => $sport->id,
        'partner_id' => $partner->id,
    ]);

    // Donor without donations
    $donorWithout = Donor::factory()->create([
        'email' => 'without@example.com',
    ]);

    // Donor with donations
    $donorWith = Donor::factory()->create([
        'email' => 'with@example.com',
    ]);

    Donation::create([
        'donator_id' => $donorWith->id,
        'athlete_id' => $athlete->id,
        'amount_per_round' => 10,
        'amount_max' => 100,
        'amount_min' => 0,
        'comment' => 'Test',
    ]);

    Queue::fake();

    Livewire::test(AdminDonorTable::class)
        ->set('checkboxValues', [$donorWithout->id, $donorWith->id])
        ->call('bulkCreateInvoice');

    // Only the donor with donations should trigger the CreateDonorInvoice job
    Queue::assertPushed(CreateDonorInvoice::class, function ($job) use ($donorWith) {
        return (int) $job->donor->id === (int) $donorWith->id;
    });

    Queue::assertNotPushed(CreateDonorInvoice::class, function ($job) use ($donorWithout) {
        return (int) $job->donor->id === (int) $donorWithout->id;
    });

    Queue::assertPushed(CreateDonorInvoice::class, 1);
});
