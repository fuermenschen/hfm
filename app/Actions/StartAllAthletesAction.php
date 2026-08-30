<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventState;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;

class StartAllAthletesAction
{
    /**
     * Starts every not-started athlete registration of an event together.
     *
     * @return int Number of newly started registrations.
     */
    public function __invoke(DonationEvent $donationEvent): int
    {
        return AthleteRegistration::query()
            ->whereBelongsTo($donationEvent)
            ->where('event_state', EventState::NotStarted->value)
            ->update(['event_state' => EventState::Running->value]);
    }
}
