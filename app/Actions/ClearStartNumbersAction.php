<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use Illuminate\Support\Facades\DB;

class ClearStartNumbersAction
{
    /**
     * Removes the start numbers of all athlete registrations of an event.
     *
     * @return int Number of cleared registrations.
     */
    public function __invoke(DonationEvent $donationEvent): int
    {
        return DB::transaction(function () use ($donationEvent): int {
            $count = AthleteRegistration::query()
                ->whereBelongsTo($donationEvent)
                ->whereNotNull('start_number')
                ->count();

            AthleteRegistration::query()
                ->whereBelongsTo($donationEvent)
                ->update(['start_number' => null]);

            return $count;
        });
    }
}
