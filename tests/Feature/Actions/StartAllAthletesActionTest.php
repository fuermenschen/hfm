<?php

use App\Actions\StartAllAthletesAction;
use App\Enums\EventState;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;

it('starts all not-started registrations of the event only', function (): void {
    $event = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();
    $notStarted = AthleteRegistration::factory()->forEvent($event)->count(2)->create();
    $running = AthleteRegistration::factory()->forEvent($event)->running()->create();
    $finished = AthleteRegistration::factory()->forEvent($event)->finished()->create();
    $otherEventRegistration = AthleteRegistration::factory()->forEvent($otherEvent)->create();

    $started = resolve(StartAllAthletesAction::class)($event);

    expect($started)->toBe(2)
        ->and($otherEventRegistration->refresh()->event_state)->toBe(EventState::NotStarted)
        ->and($running->refresh()->event_state)->toBe(EventState::Running)
        ->and($finished->refresh()->event_state)->toBe(EventState::Finished);

    $notStarted->each(function (AthleteRegistration $registration): void {
        expect($registration->refresh()->event_state)->toBe(EventState::Running);
    });
});
