<?php

use App\Actions\GetDashboardDataAction;
use App\Models\Athlete;
use App\Models\Donation;
use App\Models\Donator;
use App\Models\Partner;
use App\Models\SportType;

it('builds dashboard data with expected aggregates', function (): void {
    $sportType = SportType::query()->create(['name' => 'Run']);
    $partner = Partner::query()->create(['name' => 'Partner One']);

    $athleteOne = Athlete::factory()->create([
        'partner_id' => $partner->id,
        'sport_type_id' => $sportType->id,
        'rounds_estimated' => 10,
        'rounds_done' => 12,
        'verified' => true,
    ]);

    $athleteTwo = Athlete::factory()->create([
        'partner_id' => $partner->id,
        'sport_type_id' => $sportType->id,
        'rounds_estimated' => 5,
        'rounds_done' => 3,
        'verified' => false,
    ]);

    $donatorOne = Donator::factory()->create();
    $donatorTwo = Donator::factory()->create();

    $firstDonation = Donation::query()->create([
        'donator_id' => $donatorOne->id,
        'athlete_id' => $athleteOne->id,
        'amount_per_round' => 2.0,
        'amount_min' => null,
        'amount_max' => 30.0,
    ]);
    $firstDonation->forceFill(['verified' => true])->save();

    $secondDonation = Donation::query()->create([
        'donator_id' => $donatorTwo->id,
        'athlete_id' => $athleteTwo->id,
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
            'donatorCount',
            'donationCount',
            'verifiedAthleteCount',
            'verifiedDonationCount',
            'meanNumberOfDonations',
            'meanNumberOfRounds',
            'meanNumberOfDonationsDonator',
            'meanDonationAmount',
            'expectedDonationAmount',
            'actualTotalAmount',
            'estimatedAmounts',
            'actualAmounts',
            'mostRecentActivities',
        ])
        ->and($data['greeting'])->toBeString()->not->toBe('')
        ->and($data['athleteCount'])->toBe(2)
        ->and($data['donatorCount'])->toBe(2)
        ->and($data['donationCount'])->toBe(2)
        ->and($data['verifiedAthleteCount'])->toBe(1)
        ->and($data['verifiedDonationCount'])->toBe(1)
        ->and($data['meanNumberOfDonations'])->toBe(1.0)
        ->and($data['meanNumberOfRounds'])->toBe(7.5)
        ->and($data['meanNumberOfDonationsDonator'])->toBe(1.0)
        ->and($data['meanDonationAmount'])->toBe(1.5)
        ->and($data['expectedDonationAmount'])->toBe(30.0)
        ->and($data['actualTotalAmount'])->toBe(34.0)
        ->and($data['estimatedAmounts'])->toBe([$partner->id => 30.0])
        ->and($data['actualAmounts'])->toBe([$partner->id => 34.0])
        ->and($data['mostRecentActivities'])->toBeArray();
});
