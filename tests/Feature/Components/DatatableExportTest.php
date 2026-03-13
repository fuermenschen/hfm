<?php

use App\Components\AdminAthleteTable;
use App\Components\AdminDonationTable;
use App\Components\AdminDonatorTable;
use App\Models\Athlete;
use App\Models\Donation;
use App\Models\Donator;
use App\Models\Partner;
use App\Models\SportType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('exports selected donors as csv', function (): void {
    $donor = Donator::factory()->create();

    Livewire::test(AdminDonatorTable::class)
        ->set('checkboxValues', [$donor->id])
        ->call('exportSelected', 'csv')
        ->assertFileDownloaded();
});

it('returns null for selected donation export without selection', function (): void {
    Livewire::test(AdminDonationTable::class)
        ->call('exportSelected', 'xlsx')
        ->assertReturned(null);
});

it('exports all athletes as xlsx even when search is active', function (): void {
    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $partner = Partner::query()->create(['name' => 'Partner']);

    Athlete::factory()->create([
        'first_name' => 'Anna',
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
    ]);

    Athlete::factory()->create([
        'first_name' => 'Bea',
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
    ]);

    Livewire::test(AdminAthleteTable::class)
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

    $donator = Donator::factory()->create();

    $donation = Donation::query()->create([
        'donator_id' => $donator->id,
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
