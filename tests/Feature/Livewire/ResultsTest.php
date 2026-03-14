<?php

use App\Components\Results;
use App\Models\Athlete;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Partner;
use App\Models\SportType;
use Livewire\Livewire;

it('renders successfully and shows per-partner section', function () {
    Livewire::test(Results::class)
        ->assertStatus(200)
        ->assertSee('Spenden pro Benefizpartner:in')
        ->assertDontSee('Einzelresultate');
});

it('splits "alle zu gleichen Teilen" amount across remaining partners', function () {
    // Create partners
    $equal = Partner::create(['name' => 'alle zu gleichen Teilen']);
    $b = Partner::create(['name' => 'B Partner']);
    $c = Partner::create(['name' => 'C Partner']);

    // Create athletes assigned to partners with completed rounds
    $sportType = SportType::create(['name' => 'Run']);
    $athleteEqual = Athlete::factory()->create([
        'first_name' => 'Alice',
        'last_name' => 'Equal',
        'rounds_done' => 10, // 10 * 10 = 100
        'sport_type_id' => $sportType->id,
        'partner_id' => $equal->id,
    ]);
    $athleteB = Athlete::factory()->create([
        'first_name' => 'Bob',
        'last_name' => 'Bee',
        'rounds_done' => 5, // 5 * 10 = 50
        'sport_type_id' => $sportType->id,
        'partner_id' => $b->id,
    ]);
    $athleteC = Athlete::factory()->create([
        'first_name' => 'Cathy',
        'last_name' => 'See',
        'rounds_done' => 6, // 6 * 5 = 30
        'sport_type_id' => $sportType->id,
        'partner_id' => $c->id,
    ]);

    // Create donors and donations
    $donor1 = Donor::factory()->create();
    $donor2 = Donor::factory()->create();
    $donor3 = Donor::factory()->create();

    Donation::create([
        'donator_id' => $donor1->id,
        'athlete_id' => $athleteEqual->id,
        'amount_per_round' => 10.0,
        'amount_max' => null,
        'amount_min' => null,
        'comment' => null,
    ]);
    Donation::create([
        'donator_id' => $donor2->id,
        'athlete_id' => $athleteB->id,
        'amount_per_round' => 10.0,
        'amount_max' => null,
        'amount_min' => null,
        'comment' => null,
    ]);
    Donation::create([
        'donator_id' => $donor3->id,
        'athlete_id' => $athleteC->id,
        'amount_per_round' => 5.0,
        'amount_max' => null,
        'amount_min' => null,
        'comment' => null,
    ]);

    Livewire::test(Results::class)
        ->assertStatus(200)
        // Other partners should receive +50.00 each (100 / 2)
        ->assertSee('B Partner')
        ->assertSee('Fr. 100.00')
        ->assertSee('C Partner')
        ->assertSee('Fr. 80.00');
});

it('does not expose single athlete results anymore', function () {
    $sportType = SportType::create(['name' => 'Run']);
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
        ->assertDontSee('Einzelresultate')
        ->assertDontSee($three->privacy_name)
        ->assertDontSee($zero->privacy_name);
});
