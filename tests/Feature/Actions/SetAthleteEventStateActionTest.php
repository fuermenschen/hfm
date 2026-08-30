<?php

use App\Actions\SetAthleteEventStateAction;
use App\Enums\EventState;
use App\Models\AthleteRegistration;

it('sets the event state of a registration', function (): void {
    $registration = AthleteRegistration::factory()->create();

    resolve(SetAthleteEventStateAction::class)($registration, EventState::Running);
    expect($registration->refresh()->event_state)->toBe(EventState::Running);

    resolve(SetAthleteEventStateAction::class)($registration, EventState::Finished);
    expect($registration->refresh()->event_state)->toBe(EventState::Finished);

    resolve(SetAthleteEventStateAction::class)($registration, EventState::NotStarted);
    expect($registration->refresh()->event_state)->toBe(EventState::NotStarted);
});
