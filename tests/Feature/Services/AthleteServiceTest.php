<?php

use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Services\AthleteService;

it('returns all external users with at least one athlete registration', function (): void {
    $athlete = ExternalUser::factory()->asAthlete()->create();
    $nonAthlete = ExternalUser::factory()->create();

    $resultIds = app(AthleteService::class)->all()->pluck('id')->all();

    expect($resultIds)
        ->toContain($athlete->id)
        ->and(in_array($nonAthlete->id, $resultIds, true))->toBeFalse();
});

it('returns athletes even when registration is not verified', function (): void {
    $athlete = ExternalUser::factory()->asAthlete(registrationAttributes: ['verified' => false])->create();

    $resultIds = app(AthleteService::class)->all()->pluck('id')->all();

    expect($resultIds)->toContain($athlete->id);
});

it('returns each athlete only once when they have multiple registrations', function (): void {
    $athlete = ExternalUser::factory()->asAthlete()->asAthlete()->create();
    $otherAthlete = ExternalUser::factory()->asAthlete()->create();

    expect($athlete->athleteRegistrations()->count())->toBe(2);

    $result = app(AthleteService::class)->all()->get();
    $matches = $result->where('id', $athlete->id)->count();

    expect($matches)->toBe(1)
        ->and($result->count())->toBe(2)
        ->and($result->pluck('id')->all())->toContain($otherAthlete->id);
});

it('filters athletes by registration event in forEvent', function (): void {
    $eventA = DonationEvent::factory()->create();
    $eventB = DonationEvent::factory()->create();

    $athleteInA = ExternalUser::factory()->asAthlete($eventA)->create();
    $athleteInB = ExternalUser::factory()->asAthlete($eventB)->create();

    $resultIds = app(AthleteService::class)->forEvent($eventA)->pluck('id')->all();

    expect($resultIds)
        ->toContain($athleteInA->id)
        ->and(in_array($athleteInB->id, $resultIds, true))->toBeFalse();
});

it('returns unique athletes across multiple events in forEvents', function (): void {
    $eventA = DonationEvent::factory()->create();
    $eventB = DonationEvent::factory()->create();
    $eventC = DonationEvent::factory()->create();

    $athleteInA = ExternalUser::factory()->asAthlete($eventA)->create();
    $athleteInBandC = ExternalUser::factory()->asAthlete($eventB)->asAthlete($eventC)->create();
    $athleteInCOnly = ExternalUser::factory()->asAthlete($eventC)->create();

    $resultIds = app(AthleteService::class)
        ->forEvents([$eventA, (string) $eventB->id])
        ->pluck('id')
        ->all();

    expect($resultIds)
        ->toContain($athleteInA->id)
        ->toContain($athleteInBandC->id)
        ->and(in_array($athleteInCOnly->id, $resultIds, true))->toBeFalse();
});

it('returns empty result on empty event list in forEvents', function (): void {
    ExternalUser::factory()->asAthlete()->create();

    $count = app(AthleteService::class)->forEvents([])->count();

    expect($count)->toBe(0);
});

it('returns number of external users with at least one registration in count', function (): void {
    ExternalUser::factory()->asAthlete()->create();
    ExternalUser::factory()->asAthlete()->create();
    ExternalUser::factory()->create(); // non-athlete

    expect(app(AthleteService::class)->count())->toBe(2);
});

it('returns number of athletes with at least one verified registration in verifiedCount', function (): void {
    ExternalUser::factory()->asAthlete(registrationAttributes: ['verified' => true])->create();
    ExternalUser::factory()->asAthlete(registrationAttributes: ['verified' => false])->create();

    expect(app(AthleteService::class)->verifiedCount())->toBe(1);
});
