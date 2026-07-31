<?php

use App\Actions\GetDashboardDataAction;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;

it('builds dashboard data with expected aggregates', function (): void {
    $sportType = SportType::query()->create(['name' => 'Run']);
    $donationEvent = DonationEvent::factory()->create();
    $partner = Partner::factory()->create(['name' => 'Partner One']);

    $athleteOne = ExternalUser::factory()->create();
    $athleteOneRegistration = AthleteRegistration::factory()->create([
        'external_user_id' => $athleteOne->id,
        'donation_event_id' => $donationEvent->id,
        'partner_id' => $partner->id,
        'sport_type_id' => $sportType->id,
        'rounds_estimated' => 10,
        'rounds_done' => 12,
        'verified' => true,
    ]);

    $athleteTwo = ExternalUser::factory()->create();
    $athleteTwoRegistration = AthleteRegistration::factory()->create([
        'external_user_id' => $athleteTwo->id,
        'donation_event_id' => $donationEvent->id,
        'partner_id' => $partner->id,
        'sport_type_id' => $sportType->id,
        'rounds_estimated' => 5,
        'rounds_done' => 3,
        'verified' => false,
    ]);

    $donorOne = ExternalUser::factory()->create();
    $donorTwo = ExternalUser::factory()->create();

    $firstDonation = Donation::query()->create([
        'donor_external_user_id' => $donorOne->id,
        'athlete_registration_id' => $athleteOneRegistration->id,
        'amount_per_round' => 2.0,
        'amount_min' => null,
        'amount_max' => 30.0,
    ]);
    $firstDonation->forceFill(['verified' => true])->save();

    $secondDonation = Donation::query()->create([
        'donor_external_user_id' => $donorTwo->id,
        'athlete_registration_id' => $athleteTwoRegistration->id,
        'amount_per_round' => 1.0,
        'amount_min' => 10.0,
        'amount_max' => null,
    ]);
    $secondDonation->forceFill(['verified' => false])->save();

    $data = app(GetDashboardDataAction::class)();

    expect($data)
        ->toHaveKeys([
            'greeting',
            'partners',
            'athleteCount',
            'donorCount',
            'donationCount',
            'verifiedAthleteCount',
            'verifiedDonationCount',
            'meanNumberOfDonations',
            'meanNumberOfRounds',
            'meanNumberOfDonationsDonor',
            'meanDonationAmount',
            'expectedDonationAmount',
            'actualTotalAmount',
            'estimatedAmounts',
            'actualAmounts',
            'mostRecentActivities',
        ])
        ->and($data['greeting'])->toBeString()
        ->and($data['athleteCount'])->toBe(2)
        ->and($data['donorCount'])->toBe(2)
        ->and($data['donationCount'])->toBe(2)
        ->and($data['verifiedAthleteCount'])->toBe(1)
        ->and($data['verifiedDonationCount'])->toBe(1)
        ->and($data['meanNumberOfDonations'])->toBe(1.0)
        ->and($data['meanNumberOfRounds'])->toBe(7.5)
        ->and($data['meanNumberOfDonationsDonor'])->toBe(1.0)
        ->and($data['meanDonationAmount'])->toBe(1.5)
        ->and($data['expectedDonationAmount'])->toBe(30.0)
        ->and($data['actualTotalAmount'])->toBe(34.0)
        ->and($data['estimatedAmounts'])->toBe([$partner->id => 30.0])
        ->and($data['actualAmounts'])->toBe([$partner->id => 34.0])
        ->and($data['mostRecentActivities'])->toBeArray()
        ->and($data['greeting'] !== '')->toBeTrue();

});

it('scopes dashboard data and activities to an event', function (): void {
    $selectedEvent = DonationEvent::factory()->year(2026)->create();
    $otherEvent = DonationEvent::factory()->year(2025)->create();
    $selectedPartner = Partner::factory()->create(['name' => 'Selected Partner']);
    $otherPartner = Partner::factory()->create(['name' => 'Other Partner']);
    $selectedEvent->partners()->attach($selectedPartner);
    $otherEvent->partners()->attach($otherPartner);

    $selectedAthlete = ExternalUser::factory()->create(['first_name' => 'Selected Athlete']);
    $selectedRegistration = AthleteRegistration::factory()
        ->forEvent($selectedEvent)
        ->forExternalUser($selectedAthlete)
        ->withPartner($selectedPartner)
        ->verified()
        ->create(['rounds_estimated' => 10, 'rounds_done' => 12]);
    $selectedDonor = ExternalUser::factory()->create(['first_name' => 'Selected Donor']);
    Donation::factory()
        ->forPair($selectedDonor, $selectedRegistration)
        ->create([
            'amount_per_round' => 2,
            'amount_min' => null,
            'amount_max' => 30,
            'verified' => true,
        ]);

    $otherAthlete = ExternalUser::factory()->create(['first_name' => 'Other Athlete']);
    $otherRegistration = AthleteRegistration::factory()
        ->forEvent($otherEvent)
        ->forExternalUser($otherAthlete)
        ->withPartner($otherPartner)
        ->create(['rounds_estimated' => 100, 'rounds_done' => 100]);
    $otherDonor = ExternalUser::factory()->create(['first_name' => 'Other Donor']);
    Donation::factory()
        ->forPair($otherDonor, $otherRegistration)
        ->create(['amount_per_round' => 100, 'verified' => false]);

    $data = app(GetDashboardDataAction::class)($selectedEvent);

    expect($data['selectedEventSlug'])->toBe($selectedEvent->slug)
        ->and($data['partners']->pluck('id')->all())->toBe([$selectedPartner->id])
        ->and($data['athleteCount'])->toBe(1)
        ->and($data['verifiedAthleteCount'])->toBe(1)
        ->and($data['donorCount'])->toBe(1)
        ->and($data['donationCount'])->toBe(1)
        ->and($data['verifiedDonationCount'])->toBe(1)
        ->and($data['meanNumberOfDonations'])->toBe(1.0)
        ->and($data['meanNumberOfRounds'])->toBe(10.0)
        ->and($data['meanNumberOfDonationsDonor'])->toBe(1.0)
        ->and($data['meanDonationAmount'])->toBe(2.0)
        ->and($data['expectedDonationAmount'])->toBe(20.0)
        ->and($data['actualTotalAmount'])->toBe(24.0)
        ->and($data['estimatedAmounts'])->toBe([$selectedPartner->id => 20.0])
        ->and($data['actualAmounts'])->toBe([$selectedPartner->id => 24.0])
        ->and(collect($data['mostRecentActivities'])->pluck('type')->unique()->sort()->values()->all())
        ->toBe(['athlete_registration', 'donation', 'external_user'])
        ->and(collect($data['mostRecentActivities'])->pluck('name')->implode(' '))
        ->not->toContain('Other');
});
