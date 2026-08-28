<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventState;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;

class FinishAllAthletesAction
{
    /**
     * Marks every athlete registration of an event that is not finished yet
     * as finished.
     *
     * @return int Number of newly finished registrations.
     */
    public function __invoke(DonationEvent $donationEvent): int
    {
        return AthleteRegistration::query()
            ->whereBelongsTo($donationEvent)
            ->where('event_state', '!=', EventState::Finished->value)
            ->update(['event_state' => EventState::Finished->value]);
    }
}
