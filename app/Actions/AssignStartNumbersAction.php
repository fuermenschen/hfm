<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class AssignStartNumbersAction
{
    public const int MAX_START_NUMBER = 65535;

    /**
     * Assigns start numbers to the athlete registrations of an event, counting
     * up from the first number. With $onlyMissing, only registrations without
     * a start number receive one and already-taken numbers are skipped while
     * counting up. Otherwise all registrations are re-assigned alphabetically.
     *
     * @return int Number of assigned start numbers.
     */
    public function __invoke(DonationEvent $donationEvent, int $firstNumber, bool $onlyMissing): int
    {
        throw_if($firstNumber < 1 || $firstNumber > self::MAX_START_NUMBER, \InvalidArgumentException::class, 'Die erste Startnummer muss zwischen 1 und '.self::MAX_START_NUMBER.' liegen.');

        $query = AthleteRegistration::query()
            ->whereBelongsTo($donationEvent)
            // Not an existence check: excludes soft-deleted users, which the
            // raw join below would otherwise include (joins bypass the
            // SoftDeletes global scope).
            ->whereHas('externalUser')
            ->join('external_users', 'external_users.id', '=', 'athlete_registrations.external_user_id')
            ->orderBy('external_users.first_name')
            ->orderBy('external_users.last_name')
            ->select('athlete_registrations.*');

        if ($onlyMissing) {
            $query->whereNull('athlete_registrations.start_number');
        }

        $registrations = $query->get();

        if ($registrations->isEmpty()) {
            return 0;
        }

        $takenNumbers = $onlyMissing
            ? AthleteRegistration::query()
                ->whereBelongsTo($donationEvent)
                ->whereNotNull('start_number')
                ->pluck('start_number')
                ->map(fn (mixed $number): int => (int) $number)
                ->flip()
                ->all()
            : [];

        $assigned = 0;

        try {
            DB::transaction(function () use ($donationEvent, $registrations, $firstNumber, $onlyMissing, $takenNumbers, &$assigned): void {
                if (! $onlyMissing) {
                    AthleteRegistration::query()
                        ->whereBelongsTo($donationEvent)
                        ->update(['start_number' => null]);
                }

                $number = $firstNumber;

                foreach ($registrations as $registration) {
                    if ($onlyMissing) {
                        while (array_key_exists($number, $takenNumbers)) {
                            $number++;
                        }
                    }

                    throw_if($number > self::MAX_START_NUMBER, \InvalidArgumentException::class, 'Die Startnummern übersteigen den Maximalwert von '.self::MAX_START_NUMBER.'.');

                    $registration->start_number = $number;
                    $registration->save();

                    $number++;
                    $assigned++;
                }
            });
        } catch (UniqueConstraintViolationException) {
            // Lost a race against a concurrent assignment; the DB unique
            // index is the arbiter.
            throw new \InvalidArgumentException('Die Startnummern wurden zwischenzeitlich geändert. Bitte erneut versuchen.');
        }

        return $assigned;
    }
}
