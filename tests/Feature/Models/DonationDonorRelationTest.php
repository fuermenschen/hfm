<?php

use App\Models\Athlete;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\Donor;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

it('maps donor model to donors table', function () {
    expect((new Donor)->getTable())->toBe('donors');
});

it('uses donor has many relation on donor_id foreign key', function () {
    $donor = new Donor;
    $relation = $donor->donations();

    expect($relation)
        ->toBeInstanceOf(HasMany::class)
        ->and($relation->getForeignKeyName())->toBe('donor_id');
});

it('uses donor relation with donor_id foreign key', function () {
    $donation = new Donation;

    $donorRelation = $donation->donor();

    expect($donorRelation)
        ->toBeInstanceOf(BelongsTo::class)
        ->and($donorRelation->getRelated())->toBeInstanceOf(Donor::class)
        ->and($donorRelation->getForeignKeyName())->toBe('donor_id');
});

it('resolves new donation relationships', function () {
    $donationEvent = DonationEvent::factory()->create();
    $partner = Partner::query()->create(['name' => 'Test Partner']);
    $sportType = SportType::query()->create(['name' => 'Run']);
    $donor = Donor::factory()->create();
    $athlete = Athlete::factory()->create([
        'partner_id' => $partner->id,
        'sport_type_id' => $sportType->id,
    ]);
    $externalUser = ExternalUser::factory()->create();
    $athleteRegistration = AthleteRegistration::factory()->create([
        'donation_event_id' => $donationEvent->id,
        'sport_type_id' => $sportType->id,
    ]);

    $donation = Donation::query()->create([
        'donor_id' => $donor->id,
        'donor_external_user_id' => $externalUser->id,
        'athlete_id' => $athlete->id,
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
        ->and($donation->athleteRegistration->is($athleteRegistration))->toBeTrue();
});

it('derives donation event from athlete registration', function () {
    $donationEvent = DonationEvent::factory()->create();
    $partner = Partner::query()->create(['name' => 'Test Partner']);
    $sportType = SportType::query()->create(['name' => 'Run']);
    $donor = Donor::factory()->create();
    $athlete = Athlete::factory()->create([
        'partner_id' => $partner->id,
        'sport_type_id' => $sportType->id,
    ]);
    $externalUser = ExternalUser::factory()->create();
    $athleteRegistration = AthleteRegistration::factory()->create([
        'donation_event_id' => $donationEvent->id,
        'sport_type_id' => $sportType->id,
    ]);

    $donation = Donation::query()->create([
        'donor_id' => $donor->id,
        'donor_external_user_id' => $externalUser->id,
        'athlete_id' => $athlete->id,
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
