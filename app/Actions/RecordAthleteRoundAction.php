<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AthleteRegistration;

class RecordAthleteRoundAction
{
    /**
     * Records a counted round for an athlete registration. The delta must be
     * +1 or -1; decrements stop at zero. Returns the new round count.
     */
    public function __invoke(AthleteRegistration $athleteRegistration, int $delta): int
    {
        throw_unless(in_array($delta, [1, -1], true), \InvalidArgumentException::class, 'Die Rundenänderung muss +1 oder -1 sein.');

        if ($delta === 1) {
            // Guarded in SQL, not in memory: the column is unsignedTinyInteger
            // and strict MariaDB would reject values past 255 with a 500.
            AthleteRegistration::query()
                ->whereKey($athleteRegistration->getKey())
                ->where('rounds_done', '<', SetAthleteRoundsAction::MAX_ROUNDS)
                ->increment('rounds_done');
        } else {
            // Guarded in SQL, not in memory: two concurrent decrements must
            // never push the count below zero.
            AthleteRegistration::query()
                ->whereKey($athleteRegistration->getKey())
                ->where('rounds_done', '>', 0)
                ->decrement('rounds_done');
        }

        return (int) $athleteRegistration->refresh()->rounds_done;
    }
}
