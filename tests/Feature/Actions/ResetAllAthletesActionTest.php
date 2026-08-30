<?php

use App\Actions\ResetAllAthletesAction;
use App\Enums\EventState;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;

it('resets rounds and states of all registrations of the event', function (): void {
    $event = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();
    $running = AthleteRegistration::factory()->forEvent($event)->create([
        'rounds_done' => 4,
        'event_state' => EventState::Running->value,
    ]);
    $finished = AthleteRegistration::factory()->forEvent($event)->create([
        'rounds_done' => 7,
        'event_state' => EventState::Finished->value,
    ]);
    $pristine = AthleteRegistration::factory()->forEvent($event)->create([
        'rounds_done' => 0,
        'event_state' => EventState::NotStarted->value,
    ]);
    $otherEventRegistration = AthleteRegistration::factory()->forEvent($otherEvent)->create([
        'rounds_done' => 9,
        'event_state' => EventState::Running->value,
    ]);

    $resetCount = resolve(ResetAllAthletesAction::class)($event);

    expect($resetCount)->toBe(2)
        ->and($running->refresh()->rounds_done)->toBe(0)
        ->and($running->event_state)->toBe(EventState::NotStarted)
        ->and($finished->refresh()->rounds_done)->toBe(0)
        ->and($finished->event_state)->toBe(EventState::NotStarted)
        ->and($pristine->refresh()->rounds_done)->toBe(0)
        ->and($otherEventRegistration->refresh()->rounds_done)->toBe(9)
        ->and($otherEventRegistration->event_state)->toBe(EventState::Running);
});
