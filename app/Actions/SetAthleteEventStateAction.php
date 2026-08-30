<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventState;
use App\Models\AthleteRegistration;

class SetAthleteEventStateAction
{
    /**
     * Sets the in-event state of a single athlete registration. All
     * transitions are allowed: the paper tally list is the ground truth and
     * the digital state is corrected to match it.
     */
    public function __invoke(AthleteRegistration $athleteRegistration, EventState $eventState): void
    {
        $athleteRegistration->event_state = $eventState;
        $athleteRegistration->save();
    }
}
