<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AthleteRegistration;

class SetAthleteRoundsAction
{
    public const int MAX_ROUNDS = 255;

    /**
     * Manually sets the counted rounds of an athlete registration: the paper
     * tally list is the ground truth and the digital count is corrected to
     * match it.
     */
    public function __invoke(AthleteRegistration $athleteRegistration, int $rounds): void
    {
        throw_if($rounds < 0 || $rounds > self::MAX_ROUNDS, \InvalidArgumentException::class, 'Die Runden müssen zwischen 0 und '.self::MAX_ROUNDS.' liegen.');

        $athleteRegistration->rounds_done = $rounds;
        $athleteRegistration->save();
    }
}
