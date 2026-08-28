<?php

use App\Actions\SetAthleteRoundsAction;
use App\Models\AthleteRegistration;

it('sets the rounds of a registration', function (): void {
    $registration = AthleteRegistration::factory()->create(['rounds_done' => 2]);

    resolve(SetAthleteRoundsAction::class)($registration, 9);

    expect($registration->refresh()->rounds_done)->toBe(9);
});

it('rejects rounds out of range', function (int $rounds): void {
    $registration = AthleteRegistration::factory()->create(['rounds_done' => 2]);

    expect(fn () => resolve(SetAthleteRoundsAction::class)($registration, $rounds))
        ->toThrow(InvalidArgumentException::class);
})->with([[-1], [256]]);
