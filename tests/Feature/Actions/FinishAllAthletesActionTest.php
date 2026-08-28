<?php

use App\Actions\FinishAllAthletesAction;
use App\Enums\EventState;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;

it('finishes all registrations of the event that are not finished yet', function (): void {
    $event = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();
    $notStarted = AthleteRegistration::factory()->forEvent($event)->create();
    $running = AthleteRegistration::factory()->forEvent($event)->running()->create();
    $finished = AthleteRegistration::factory()->forEvent($event)->finished()->create();
    $otherEventRegistration = AthleteRegistration::factory()->forEvent($otherEvent)->create();

    $finishedCount = resolve(FinishAllAthletesAction::class)($event);

    expect($finishedCount)->toBe(2)
        ->and($notStarted->refresh()->event_state)->toBe(EventState::Finished)
        ->and($running->refresh()->event_state)->toBe(EventState::Finished)
        ->and($finished->refresh()->event_state)->toBe(EventState::Finished)
        ->and($otherEventRegistration->refresh()->event_state)->toBe(EventState::NotStarted);
});
