<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventState;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use Illuminate\Database\Eloquent\Builder;

class ResetAllAthletesAction
{
    /**
     * Resets every athlete registration of an event: rounds back to zero and
     * state back to not started.
     *
     * @return int Number of changed registrations.
     */
    public function __invoke(DonationEvent $donationEvent): int
    {
        return AthleteRegistration::query()
            ->whereBelongsTo($donationEvent)
            ->where(function (Builder $query): void {
                $query
                    ->where('rounds_done', '!=', 0)
                    ->orWhere('event_state', '!=', EventState::NotStarted->value);
            })
            ->update([
                'rounds_done' => 0,
                'event_state' => EventState::NotStarted->value,
            ]);
    }
}
