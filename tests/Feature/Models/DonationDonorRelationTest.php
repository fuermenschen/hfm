<?php

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;

it('resolves new donation relationships', function () {
    $donationEvent = DonationEvent::factory()->create();
    $partner = Partner::query()->create(['name' => 'Test Partner']);
    $sportType = SportType::query()->create(['name' => 'Run']);
    $athleteIdentity = ExternalUser::factory()->create();
    $externalUser = ExternalUser::factory()->create();
    $athleteRegistration = AthleteRegistration::factory()->create([
        'donation_event_id' => $donationEvent->id,
        'external_user_id' => $athleteIdentity->id,
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
    ]);

    $donation = Donation::query()->create([
        'donor_external_user_id' => $externalUser->id,
        'athlete_registration_id' => $athleteRegistration->id,
        'amount_per_round' => 10,
        'amount_max' => 100,
        'amount_min' => 10,
        'comment' => 'Test donation',
    ]);
    $donation->refresh();

    expect($donation->donorExternalUser)
        ->toBeInstanceOf(ExternalUser::class)
        ->and($donation->donorExternalUser->is($externalUser))->toBeTrue()
        ->and($donation->athleteRegistration)->toBeInstanceOf(AthleteRegistration::class)
        ->and($donation->athleteRegistration->is($athleteRegistration))->toBeTrue()
        ->and($donation->athleteRegistration->externalUser->is($athleteIdentity))->toBeTrue();
});

it('derives donation event from athlete registration', function () {
    $donationEvent = DonationEvent::factory()->create();
    $partner = Partner::query()->create(['name' => 'Test Partner']);
    $sportType = SportType::query()->create(['name' => 'Run']);
    $athleteIdentity = ExternalUser::factory()->create();
    $externalUser = ExternalUser::factory()->create();
    $athleteRegistration = AthleteRegistration::factory()->create([
        'donation_event_id' => $donationEvent->id,
        'external_user_id' => $athleteIdentity->id,
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
    ]);

    $donation = Donation::query()->create([
        'donor_external_user_id' => $externalUser->id,
        'athlete_registration_id' => $athleteRegistration->id,
        'amount_per_round' => 10,
        'amount_max' => 100,
        'amount_min' => 10,
        'comment' => 'Test donation',
    ]);

    expect($donation->athleteRegistration->donationEvent->is($donationEvent))->toBeTrue();
});

it('resolves donation event athlete registrations relation', function () {
    $donationEvent = DonationEvent::factory()->create();
    $sportType = SportType::query()->create(['name' => 'Run']);
    $registration = AthleteRegistration::factory()->create([
        'donation_event_id' => $donationEvent->id,
        'sport_type_id' => $sportType->id,
    ]);

    expect($donationEvent->athleteRegistrations)
        ->toHaveCount(1)
        ->and($donationEvent->athleteRegistrations->first()?->is($registration))->toBeTrue();
});
