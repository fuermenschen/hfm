<?php

use App\Components\AdminDonationTable;
use App\Components\AdminExternalUserTable;
use App\Models\Athlete;
use App\Models\Donation;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('exports selected external users as csv', function (): void {
    $externalUser = ExternalUser::factory()->create();

    Livewire::test(AdminExternalUserTable::class)
        ->set('checkboxValues', [$externalUser->id])
        ->call('exportSelected', 'csv')
        ->assertFileDownloaded();
});

it('returns null for selected donation export without selection', function (): void {
    Livewire::test(AdminDonationTable::class)
        ->call('exportSelected', 'xlsx')
        ->assertReturned(null);
});

it('exports all external users as xlsx even when search is active', function (): void {
    ExternalUser::factory()->create([
        'first_name' => 'Anna',
    ]);

    ExternalUser::factory()->create([
        'first_name' => 'Bea',
    ]);

    Livewire::test(AdminExternalUserTable::class)
        ->set('search', 'Anna')
        ->call('exportAll', 'xlsx')
        ->assertFileDownloaded();
});

it('exports selected donations as csv', function (): void {
    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $partner = Partner::query()->create(['name' => 'Partner']);

    $athlete = Athlete::factory()->create([
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
    ]);

    $donor = ExternalUser::factory()->create();

    $donation = Donation::query()->create([
        'donor_external_user_id' => $donor->id,
        'athlete_id' => $athlete->id,
        'amount_per_round' => 10,
        'amount_max' => 100,
        'amount_min' => 0,
        'comment' => 'Export test',
    ]);

    Livewire::test(AdminDonationTable::class)
        ->set('checkboxValues', [$donation->id])
        ->call('exportSelected', 'csv')
        ->assertFileDownloaded();
});
