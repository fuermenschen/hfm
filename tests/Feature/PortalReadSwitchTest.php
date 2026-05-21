<?php

use App\Models\Athlete;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\Donor;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('renders merged external user portal data grouped by donation event', function (): void {
    $sportType = SportType::query()->create(['name' => 'Laufen']);

    $eventA = DonationEvent::factory()->create([
        'title' => 'Event Alpha',
        'starts_at' => '2035-09-12 10:00:00',
    ]);
    $eventB = DonationEvent::factory()->create([
        'title' => 'Event Beta',
        'starts_at' => '2036-09-12 10:00:00',
    ]);

    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Alex',
        'last_name' => 'Muster',
    ]);

    $partner = Partner::factory()->create();

    $supporter = ExternalUser::factory()->create([
        'first_name' => 'Pat',
        'last_name' => 'Support',
    ]);

    $athleteRegistration = AthleteRegistration::factory()->create([
        'external_user_id' => $externalUser->id,
        'donation_event_id' => $eventA->id,
        'sport_type_id' => $sportType->id,
        'rounds_estimated' => 8,
        'verified' => true,
    ]);

    Donation::query()->create([
        'donor_id' => Donor::factory()->create()->id,
        'athlete_id' => Athlete::factory()->create([
            'donation_event_id' => $eventA->id,
            'partner_id' => $partner->id,
            'sport_type_id' => $sportType->id,
            'verified' => true,
        ])->id,
        'donor_external_user_id' => $supporter->id,
        'athlete_registration_id' => $athleteRegistration->id,
        'amount_per_round' => 11,
        'amount_min' => 20,
        'amount_max' => 100,
        'comment' => 'Viel Erfolg',
        'verified' => true,
    ]);

    $otherAthlete = ExternalUser::factory()->create([
        'first_name' => 'Sam',
        'last_name' => 'Runner',
    ]);
    $otherRegistration = AthleteRegistration::factory()->create([
        'external_user_id' => $otherAthlete->id,
        'donation_event_id' => $eventB->id,
        'sport_type_id' => $sportType->id,
        'rounds_estimated' => 12,
        'verified' => true,
    ]);

    Donation::query()->create([
        'donor_id' => Donor::factory()->create()->id,
        'athlete_id' => Athlete::factory()->create([
            'donation_event_id' => $eventB->id,
            'partner_id' => $partner->id,
            'sport_type_id' => $sportType->id,
            'verified' => true,
        ])->id,
        'donor_external_user_id' => $externalUser->id,
        'athlete_registration_id' => $otherRegistration->id,
        'amount_per_round' => 7,
        'amount_min' => 15,
        'amount_max' => 90,
        'comment' => null,
        'verified' => false,
    ]);

    actingAs($externalUser, 'external');

    get(route('portal.dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Hallo Alex')
        ->assertSeeText('Event Alpha')
        ->assertSeeText('Event Beta')
        ->assertSeeText('Ich bin Sportler:in')
        ->assertSeeText('Ich spende')
        ->assertSeeText($supporter->privacy_name)
        ->assertSeeText($otherAthlete->privacy_name);
});
