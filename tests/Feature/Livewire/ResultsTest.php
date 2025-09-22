<?php

use App\Components\Results;
use App\Models\Athlete;
use App\Models\Partner;
use App\Services\DonationService;
use Livewire\Livewire;

it('renders successfully and shows per-partner section', function () {
    Livewire::test(Results::class)
        ->assertStatus(200)
        ->assertSee('Resultate')
        ->assertSee('Einzelresultate')
        ->assertSee('Spenden pro Benefizpartner:in');
});

it('splits "alle zu gleichen Teilen" amount across remaining partners', function () {
    // Create partners
    $equal = Partner::create(['name' => 'alle zu gleichen Teilen']);
    $b = Partner::create(['name' => 'B Partner']);
    $c = Partner::create(['name' => 'C Partner']);

    // Mock donation service calculations
    $this->mock(DonationService::class, function ($mock) use ($equal, $b, $c) {
        $mock->shouldReceive('calculateActualTotal')->andReturn(0.0);
        $mock->shouldReceive('calculateActualTotalPerPartner')->andReturn([
            $equal->id => 100.0,
            $b->id => 50.0,
            $c->id => 30.0,
        ]);
        $mock->shouldReceive('calculateActualTotalForAthlete')->andReturn(0.0);
    });

    Livewire::test(Results::class)
        ->assertStatus(200)
        // Special partner should be removed from listing
        ->assertDontSee('alle zu gleichen Teilen')
        // Other partners should receive +50.00 each (100 / 2)
        ->assertSee('B Partner')
        ->assertSee('Fr. 100.00')
        ->assertSee('C Partner')
        ->assertSee('Fr. 80.00');
});

it('filters out athletes with zero rounds in the table', function () {
    // Mock donation service to avoid unrelated calculations
    $this->mock(DonationService::class, function ($mock) {
        $mock->shouldReceive('calculateActualTotal')->andReturn(0.0);
        $mock->shouldReceive('calculateActualTotalPerPartner')->andReturn([]);
        $mock->shouldReceive('calculateActualTotalForAthlete')->andReturn(0.0);
    });

    $sportType = \App\Models\SportType::create(['name' => 'Run']);
    $partner = Partner::create(['name' => 'Partner X']);

    $zero = Athlete::factory()->create([
        'first_name' => 'Zero',
        'last_name' => 'Rounds',
        'rounds_done' => 0,
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
    ]);

    $three = Athlete::factory()->create([
        'first_name' => 'Three',
        'last_name' => 'Rounds',
        'rounds_done' => 3,
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
    ]);

    Livewire::test(Results::class)
        ->assertStatus(200)
        ->assertSee('Einzelresultate')
        // Should show the athlete with > 0 rounds
        ->assertSee($three->privacy_name)
        // Should NOT show the athlete with 0 rounds
        ->assertDontSee($zero->privacy_name);
});
