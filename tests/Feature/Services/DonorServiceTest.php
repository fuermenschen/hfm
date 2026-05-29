<?php

use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Services\DonorService;

it('returns all external users with at least one donation', function (): void {
    $donor = ExternalUser::factory()->asDonor()->create();
    $nonDonor = ExternalUser::factory()->create();

    $resultIds = app(DonorService::class)->all()->pluck('id')->all();

    expect($resultIds)
        ->toContain($donor->id)
        ->and(in_array($nonDonor->id, $resultIds, true))->toBeFalse();
});

it('returns donors even when donation is not verified', function (): void {
    $donor = ExternalUser::factory()->asDonor(donationAttributes: ['verified' => false])->create();

    $resultIds = app(DonorService::class)->all()->pluck('id')->all();

    expect($resultIds)->toContain($donor->id);
});

it('returns each donor only once when they have multiple donations', function (): void {
    $donor = ExternalUser::factory()->asDonor()->asDonor()->create();
    $otherDonor = ExternalUser::factory()->asDonor()->create();

    expect($donor->donationsAsDonor()->count())->toBe(2);

    $result = app(DonorService::class)->all()->get();
    $matches = $result->where('id', $donor->id)->count();

    expect($matches)->toBe(1)
        ->and($result->count())->toBe(2)
        ->and($result->pluck('id')->all())->toContain($otherDonor->id);
});

it('filters donors by athlete registration event in forEvent', function (): void {
    $eventA = DonationEvent::factory()->create();
    $eventB = DonationEvent::factory()->create();

    $donorInA = ExternalUser::factory()->asDonor($eventA)->create();
    $donorInB = ExternalUser::factory()->asDonor($eventB)->create();

    $resultIds = app(DonorService::class)->forEvent($eventA)->pluck('id')->all();

    expect($resultIds)
        ->toContain($donorInA->id)
        ->and(in_array($donorInB->id, $resultIds, true))->toBeFalse();
});

it('returns unique donors across multiple events in forEvents', function (): void {
    $eventA = DonationEvent::factory()->create();
    $eventB = DonationEvent::factory()->create();
    $eventC = DonationEvent::factory()->create();

    $donorInA = ExternalUser::factory()->asDonor($eventA)->create();
    $donorInBandC = ExternalUser::factory()->asDonor($eventB)->asDonor($eventC)->create();
    $donorInCOnly = ExternalUser::factory()->asDonor($eventC)->create();

    $resultIds = app(DonorService::class)
        ->forEvents([$eventA, (string) $eventB->id])
        ->pluck('id')
        ->all();

    expect($resultIds)
        ->toContain($donorInA->id)
        ->toContain($donorInBandC->id)
        ->and(in_array($donorInCOnly->id, $resultIds, true))->toBeFalse();
});

it('returns empty result on empty event list in forEvents', function (): void {
    ExternalUser::factory()->asDonor()->create();

    $count = app(DonorService::class)->forEvents([])->count();

    expect($count)->toBe(0);
});

it('returns number of external users with at least one donation in count', function (): void {
    ExternalUser::factory()->asDonor()->create();
    ExternalUser::factory()->asDonor()->create();
    ExternalUser::factory()->create(); // non-donor

    expect(app(DonorService::class)->count())->toBe(2);
});
