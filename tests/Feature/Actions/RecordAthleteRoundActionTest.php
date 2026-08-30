<?php

use App\Actions\RecordAthleteRoundAction;
use App\Models\AthleteRegistration;

it('adds and removes rounds and stops at zero', function (): void {
    $registration = AthleteRegistration::factory()->create(['rounds_done' => 2]);
    $action = resolve(RecordAthleteRoundAction::class);

    expect($action($registration, 1))->toBe(3)
        ->and($action($registration, -1))->toBe(2)
        ->and($action($registration, -1))->toBe(1)
        ->and($action($registration, -1))->toBe(0)
        ->and($action($registration, -1))->toBe(0)
        ->and($registration->refresh()->rounds_done)->toBe(0);
});

it('preserves increments from independently loaded counter sessions', function (): void {
    $registration = AthleteRegistration::factory()->create(['rounds_done' => 2]);
    $otherSessionRegistration = AthleteRegistration::query()->findOrFail($registration->id);
    $action = resolve(RecordAthleteRoundAction::class);

    $action($registration, 1);
    $action($otherSessionRegistration, 1);

    expect($registration->refresh()->rounds_done)->toBe(4);
});

it('rejects deltas other than plus and minus one', function (): void {
    $registration = AthleteRegistration::factory()->create(['rounds_done' => 2]);

    expect(fn () => resolve(RecordAthleteRoundAction::class)($registration, 2))
        ->toThrow(InvalidArgumentException::class);
});
