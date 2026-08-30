<?php

use App\Actions\SetStartNumberAction;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\ExternalUser;

it('sets and clears a start number', function (): void {
    $registration = AthleteRegistration::factory()
        ->forEvent(DonationEvent::factory()->create())
        ->forExternalUser(ExternalUser::factory()->asAthlete()->create())
        ->create();
    $action = resolve(SetStartNumberAction::class);

    $action($registration, 42);
    expect($registration->refresh()->start_number)->toBe(42);

    $action($registration, null);
    expect($registration->refresh()->start_number)->toBeNull();
});

it('rejects a number that is taken within the same event but allows it in other events', function (): void {
    $event = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();
    $user = ExternalUser::factory()->asAthlete()->create();
    $taken = AthleteRegistration::factory()->forEvent($event)->forExternalUser($user)->withStartNumber(7)->create();
    $registration = AthleteRegistration::factory()->forEvent($event)->forExternalUser(ExternalUser::factory()->asAthlete()->create())->create();
    $registrationOtherEvent = AthleteRegistration::factory()->forEvent($otherEvent)->forExternalUser($user)->create();
    $action = resolve(SetStartNumberAction::class);

    $action($registrationOtherEvent, 7);
    expect($registrationOtherEvent->refresh()->start_number)->toBe(7);

    expect(fn () => $action($registration, 7))->toThrow(InvalidArgumentException::class)
        ->and($registration->refresh()->start_number)->toBeNull();

    $action($taken, 7);
    expect($taken->refresh()->start_number)->toBe(7);
});

it('rejects numbers out of range', function (int $number): void {
    $registration = AthleteRegistration::factory()->create();

    expect(fn () => resolve(SetStartNumberAction::class)($registration, $number))
        ->toThrow(InvalidArgumentException::class);
})->with([[0], [-3], [65536]]);
