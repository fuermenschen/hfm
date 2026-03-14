<?php

use App\Components\AdminDonatorTable;
use App\Models\Athlete;
use App\Models\Donation;
use App\Models\Donator;
use App\Models\Partner;
use App\Models\SportType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders precomputed invoice totals in the donor table', function (): void {
    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $partner = Partner::query()->create(['name' => 'HfM']);

    $donor = Donator::factory()->create();

    $athleteA = Athlete::factory()->create([
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
        'rounds_done' => 10,
    ]);

    $athleteB = Athlete::factory()->create([
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
        'rounds_done' => 10,
    ]);

    Donation::query()->create([
        'donator_id' => $donor->id,
        'athlete_id' => $athleteA->id,
        'amount_per_round' => 12,
        'amount_min' => null,
        'amount_max' => 100,
    ]);

    Donation::query()->create([
        'donator_id' => $donor->id,
        'athlete_id' => $athleteB->id,
        'amount_per_round' => 3,
        'amount_min' => 40,
        'amount_max' => null,
    ]);

    Livewire::test(AdminDonatorTable::class)
        ->assertSee('Fr. 140.00');
});

it('renders zero as invoice total when donor has no donations', function (): void {
    Donator::factory()->create();

    Livewire::test(AdminDonatorTable::class)
        ->assertSee('Fr. 0.00');
});
