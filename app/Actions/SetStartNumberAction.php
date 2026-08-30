<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AthleteRegistration;
use Illuminate\Database\UniqueConstraintViolationException;

class SetStartNumberAction
{
    /**
     * Sets or clears the start number of a single athlete registration.
     * A number must be unique within its event.
     */
    public function __invoke(AthleteRegistration $athleteRegistration, ?int $startNumber): void
    {
        if ($startNumber === null) {
            $athleteRegistration->start_number = null;
            $athleteRegistration->save();

            return;
        }

        throw_if($startNumber < 1 || $startNumber > AssignStartNumbersAction::MAX_START_NUMBER, \InvalidArgumentException::class, 'Die Startnummer muss zwischen 1 und '.AssignStartNumbersAction::MAX_START_NUMBER.' liegen.');

        $isTaken = AthleteRegistration::query()
            ->whereBelongsTo($athleteRegistration->donationEvent)
            ->where('start_number', $startNumber)
            ->whereKeyNot($athleteRegistration->getKey())
            ->exists();

        throw_if($isTaken, \InvalidArgumentException::class, 'Die Startnummer '.$startNumber.' ist in diesem Anlass bereits vergeben.');

        try {
            $athleteRegistration->start_number = $startNumber;
            $athleteRegistration->save();
        } catch (UniqueConstraintViolationException) {
            // Lost a race against a concurrent assignment; the DB unique
            // index is the arbiter.
            throw new \InvalidArgumentException('Die Startnummer '.$startNumber.' wurde zwischenzeitlich vergeben. Bitte erneut versuchen.');
        }
    }
}
