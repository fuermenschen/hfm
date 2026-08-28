<?php

use App\Actions\ClearStartNumbersAction;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\ExternalUser;

it('clears only the start numbers of the given event', function (): void {
    $event = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();
    $numbered = AthleteRegistration::factory()->forEvent($event)
        ->forExternalUser(ExternalUser::factory()->asAthlete()->create())
        ->withStartNumber(1)->create();
    $unnumbered = AthleteRegistration::factory()->forEvent($event)
        ->forExternalUser(ExternalUser::factory()->asAthlete()->create())
        ->create();
    $otherEventRegistration = AthleteRegistration::factory()->forEvent($otherEvent)
        ->forExternalUser(ExternalUser::factory()->asAthlete()->create())
        ->withStartNumber(2)->create();

    $cleared = resolve(ClearStartNumbersAction::class)($event);

    expect($cleared)->toBe(1)
        ->and($numbered->refresh()->start_number)->toBeNull()
        ->and($unnumbered->refresh()->start_number)->toBeNull()
        ->and($otherEventRegistration->refresh()->start_number)->toBe(2);
});
