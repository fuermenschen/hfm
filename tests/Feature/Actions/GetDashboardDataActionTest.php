<?php

use App\Actions\GetDashboardDataAction;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use Carbon\Carbon;

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
            'eventGroupCount',
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
        ->and($data['eventGroupCount'])->toBe(0)
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

it('distributes equal-split donations across event partners', function (): void {
    $event = DonationEvent::factory()->create(['has_equal_split_option' => true]);
    $sportType = SportType::query()->create(['name' => 'Run']);
    $firstPartner = Partner::factory()->create(['name' => 'First Partner']);
    $secondPartner = Partner::factory()->create(['name' => 'Second Partner']);
    $thirdPartner = Partner::factory()->create(['name' => 'Third Partner']);
    $event->partners()->attach([$firstPartner->id, $secondPartner->id, $thirdPartner->id]);

    $registration = AthleteRegistration::factory()->create([
        'donation_event_id' => $event->id,
        'sport_type_id' => $sportType->id,
        'partner_id' => null,
        'rounds_estimated' => 10,
        'rounds_done' => 12,
    ]);
    Donation::factory()
        ->forPair(ExternalUser::factory()->create(), $registration)
        ->create([
            'amount_per_round' => 10,
            'amount_min' => null,
            'amount_max' => null,
        ]);

    $data = app(GetDashboardDataAction::class)($event);

    expect($data['estimatedAmounts'])->toBe([
        $firstPartner->id => 33.34,
        $secondPartner->id => 33.33,
        $thirdPartner->id => 33.33,
    ])->and($data['actualAmounts'])->toBe([
        $firstPartner->id => 40.0,
        $secondPartner->id => 40.0,
        $thirdPartner->id => 40.0,
    ]);
});

it('builds cumulative chart data relative to each event start', function (): void {
    Carbon::setTestNow('2026-10-01 12:00:00');
    $event = DonationEvent::factory()->year(2026)->create(['starts_at' => '2026-09-12 11:00:00']);
    $sportType = SportType::query()->create(['name' => 'Run']);
    $athlete = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->create([
        'donation_event_id' => $event->id,
        'external_user_id' => $athlete->id,
        'sport_type_id' => $sportType->id,
        'rounds_estimated' => 10,
        'created_at' => '2026-09-10 15:00:00',
    ]);
    $donor = ExternalUser::factory()->create();
    Donation::factory()->forPair($donor, $registration)->create([
        'amount_per_round' => 2,
        'amount_min' => null,
        'amount_max' => null,
        'created_at' => '2026-09-11 15:00:00',
    ]);

    $data = app(GetDashboardDataAction::class)($event);
    Carbon::setTestNow();

    expect($data['chartEvents'])->toBe([
        ['field' => 'event_'.$event->id, 'slug' => $event->slug, 'label' => $event->title.' ('.$event->slug.')', 'colorIndex' => 0],
    ])
        ->and($data['chartTickValues'])->toBe([-60, -30, 0, 14])
        ->and($data['chartData']['registrations'])->toHaveCount(75)
        ->and($data['chartData']['registrations'][0])->toBe(['day' => -60, 'event_'.$event->id => 0])
        ->and($data['chartData']['registrations'][74])->toBe(['day' => 14, 'event_'.$event->id => 1])
        ->and($data['chartData']['donations'][0])->toBe(['day' => -60, 'event_'.$event->id => 0])
        ->and($data['chartData']['donations'][74])->toBe(['day' => 14, 'event_'.$event->id => 1])
        ->and($data['chartData']['expectedAmount'][74])->toBe(['day' => 14, 'event_'.$event->id => 20.0]);
});

it('compares all events in dashboard charts', function (): void {
    Carbon::setTestNow('2026-10-01 12:00:00');
    $firstEvent = DonationEvent::factory()->year(2025)->create(['starts_at' => '2025-09-12 11:00:00']);
    $secondEvent = DonationEvent::factory()->year(2026)->create(['starts_at' => '2026-09-12 11:00:00', 'is_published' => false]);
    $sportType = SportType::query()->create(['name' => 'Run']);

    AthleteRegistration::factory()->create([
        'donation_event_id' => $firstEvent->id,
        'sport_type_id' => $sportType->id,
        'created_at' => '2025-09-11 15:00:00',
    ]);
    AthleteRegistration::factory()->create([
        'donation_event_id' => $secondEvent->id,
        'sport_type_id' => $sportType->id,
        'created_at' => '2026-09-12 15:00:00',
    ]);

    $data = app(GetDashboardDataAction::class)();
    Carbon::setTestNow();

    expect($data['chartEvents'])->toHaveCount(2)
        ->and(collect($data['chartEvents'])->pluck('label')->all())->toBe([
            $secondEvent->title.' ('.$secondEvent->slug.') - NICHT VERÖFFENTLICHT',
            $firstEvent->title.' ('.$firstEvent->slug.')',
        ])
        ->and($data['chartData']['registrations'][0])->toBe([
            'day' => -60,
            'event_'.$secondEvent->id => 0,
            'event_'.$firstEvent->id => 0,
        ])
        ->and($data['chartData']['registrations'][74])->toBe([
            'day' => 14,
            'event_'.$secondEvent->id => 1,
            'event_'.$firstEvent->id => 1,
        ]);
});

it('limits chart activity to sixty days before through fourteen days after an event', function (): void {
    Carbon::setTestNow('2026-10-01 12:00:00');
    $event = DonationEvent::factory()->year(2026)->create(['starts_at' => '2026-09-12 11:00:00']);
    $sportType = SportType::query()->create(['name' => 'Run']);

    $earlyRegistration = AthleteRegistration::factory()->create([
        'donation_event_id' => $event->id,
        'sport_type_id' => $sportType->id,
        'created_at' => '2026-06-01 15:00:00',
    ]);
    AthleteRegistration::factory()->create([
        'donation_event_id' => $event->id,
        'sport_type_id' => $sportType->id,
        'created_at' => '2026-09-26 15:00:00',
    ]);
    AthleteRegistration::factory()->create([
        'donation_event_id' => $event->id,
        'sport_type_id' => $sportType->id,
        'created_at' => '2026-09-27 15:00:00',
    ]);
    $donor = ExternalUser::factory()->create();
    Donation::factory()->forPair($donor, $earlyRegistration)->create(['created_at' => '2026-06-01 15:00:00']);
    Donation::factory()->forPair(ExternalUser::factory()->create(), $earlyRegistration)->create(['created_at' => '2026-09-27 15:00:00']);

    $data = app(GetDashboardDataAction::class)($event);
    Carbon::setTestNow();

    expect($data['chartTickValues'])->toBe([-60, -30, 0, 14])
        ->and($data['chartData']['registrations'])->toHaveCount(75)
        ->and($data['chartData']['registrations'][0])->toBe(['day' => -60, 'event_'.$event->id => 1])
        ->and($data['chartData']['registrations'][74])->toBe(['day' => 14, 'event_'.$event->id => 2])
        ->and($data['chartData']['donations'][0])->toBe(['day' => -60, 'event_'.$event->id => 1])
        ->and($data['chartData']['donations'][74])->toBe(['day' => 14, 'event_'.$event->id => 1]);
});

it('marks today relative to events that are in preparation', function (): void {
    Carbon::setTestNow('2026-09-02 12:00:00');
    $event = DonationEvent::factory()->year(2026)->create(['starts_at' => '2026-09-12 11:00:00']);
    $sportType = SportType::query()->create(['name' => 'Run']);

    AthleteRegistration::factory()->create([
        'donation_event_id' => $event->id,
        'sport_type_id' => $sportType->id,
        'created_at' => '2026-08-23 15:00:00',
    ]);
    $data = app(GetDashboardDataAction::class)($event);
    Carbon::setTestNow();

    expect($data['chartTodayMarkers'])->toBe([['slug' => $event->slug, 'day' => -10]])
        ->and($data['chartTickValues'])->toBe([-60, -30, -10, 0, 14])
        ->and($data['chartData']['registrations'][74])->toBe(['day' => 14, 'event_'.$event->id => 1]);
});
