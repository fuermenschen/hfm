<?php

use App\Components\Results;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
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
    $equal = Partner::factory()->create(['name' => 'alle zu gleichen Teilen']);
    $b = Partner::factory()->create(['name' => 'B Partner']);
    $c = Partner::factory()->create(['name' => 'C Partner']);
    $donationEvent = DonationEvent::factory()->create(['has_equal_split_option' => true]);

    // Create athletes assigned to partners with completed rounds
    $sportType = SportType::create(['name' => 'Run']);
    $athleteEqual = ExternalUser::factory()->create([
        'first_name' => 'Alice',
        'last_name' => 'Equal',
    ]);
    $registrationEqual = AthleteRegistration::factory()->create([
        'external_user_id' => $athleteEqual->id,
        'donation_event_id' => $donationEvent->id,
        'rounds_done' => 10, // 10 * 10 = 100
        'sport_type_id' => $sportType->id,
        'partner_id' => null,
    ]);
    $athleteB = ExternalUser::factory()->create([
        'first_name' => 'Bob',
        'last_name' => 'Bee',
    ]);
    $registrationB = AthleteRegistration::factory()->create([
        'external_user_id' => $athleteB->id,
        'donation_event_id' => $donationEvent->id,
        'rounds_done' => 5, // 5 * 10 = 50
        'sport_type_id' => $sportType->id,
        'partner_id' => $b->id,
    ]);
    $athleteC = ExternalUser::factory()->create([
        'first_name' => 'Cathy',
        'last_name' => 'See',
    ]);
    $registrationC = AthleteRegistration::factory()->create([
        'external_user_id' => $athleteC->id,
        'donation_event_id' => $donationEvent->id,
        'rounds_done' => 6, // 6 * 5 = 30
        'sport_type_id' => $sportType->id,
        'partner_id' => $c->id,
    ]);

    // Create donor identities and donations
    $donor1 = ExternalUser::factory()->create();
    $donor2 = ExternalUser::factory()->create();
    $donor3 = ExternalUser::factory()->create();

    Donation::create([
        'donor_external_user_id' => $donor1->id,
        'athlete_registration_id' => $registrationEqual->id,
        'amount_per_round' => 10.0,
        'amount_max' => null,
        'amount_min' => null,
        'comment' => null,
    ]);
    Donation::create([
        'donor_external_user_id' => $donor2->id,
        'athlete_registration_id' => $registrationB->id,
        'amount_per_round' => 10.0,
        'amount_max' => null,
        'amount_min' => null,
        'comment' => null,
    ]);
    Donation::create([
        'donor_external_user_id' => $donor3->id,
        'athlete_registration_id' => $registrationC->id,
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
    $partner = Partner::factory()->create(['name' => 'Partner X']);
    $donationEvent = DonationEvent::factory()->create();

    $zero = ExternalUser::factory()->create([
        'first_name' => 'Zero',
        'last_name' => 'Rounds',
    ]);
    AthleteRegistration::factory()->create([
        'external_user_id' => $zero->id,
        'donation_event_id' => $donationEvent->id,
        'rounds_done' => 0,
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
    ]);

    $three = ExternalUser::factory()->create([
        'first_name' => 'Three',
        'last_name' => 'Rounds',
    ]);
    AthleteRegistration::factory()->create([
        'external_user_id' => $three->id,
        'donation_event_id' => $donationEvent->id,
        'rounds_done' => 3,
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
    ]);

    Livewire::test(Results::class)
        ->assertStatus(200)
        ->assertDontSee('Einzelresultate')
        ->assertDontSee($three->privacyName())
        ->assertDontSee($zero->privacyName());
});

it('counts unique donors via external user identities', function () {
    $partner = Partner::factory()->create(['name' => 'Partner X']);
    $sportType = SportType::create(['name' => 'Run']);
    $donationEvent = DonationEvent::factory()->create();

    $athleteOne = ExternalUser::factory()->create();
    $registrationOne = AthleteRegistration::factory()->create([
        'external_user_id' => $athleteOne->id,
        'donation_event_id' => $donationEvent->id,
        'rounds_done' => 3,
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
    ]);
    $athleteTwo = ExternalUser::factory()->create();
    $registrationTwo = AthleteRegistration::factory()->create([
        'external_user_id' => $athleteTwo->id,
        'donation_event_id' => $donationEvent->id,
        'rounds_done' => 2,
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
    ]);

    $donor = ExternalUser::factory()->create();

    Donation::create([
        'donor_external_user_id' => $donor->id,
        'athlete_registration_id' => $registrationOne->id,
        'amount_per_round' => 5.0,
        'amount_max' => null,
        'amount_min' => null,
        'comment' => null,
    ]);

    Donation::create([
        'donor_external_user_id' => $donor->id,
        'athlete_registration_id' => $registrationTwo->id,
        'amount_per_round' => 10.0,
        'amount_max' => null,
        'amount_min' => null,
        'comment' => null,
    ]);

    Livewire::test(Results::class)
        ->assertStatus(200)
        ->assertSee('Spender:innen')
        ->assertSee('1');
});
