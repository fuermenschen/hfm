<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Support\DonationEventIdParser;
use Illuminate\Database\Eloquent\Builder;

class AthleteService
{
    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function __construct(private DonationEventIdParser $donationEventIdParser) {}

    public function all(): Builder
    {
        return $this->baseQuery();
    }

    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function forEvent(DonationEvent|int $event): Builder
    {
        $eventId = $event instanceof DonationEvent ? $event->id : $event;

        return $this->forEvents([$eventId]);
    }

    public function forEvents(iterable $events): Builder
    {
        $eventIds = ($this->donationEventIdParser)($events);

        if ($eventIds === []) {
            return $this->baseQuery()->whereRaw('1 = 0');
        }

        return $this->baseQuery()->whereHas('athleteRegistrations', function (Builder $registrations) use ($eventIds): void {
            $registrations->whereIn('donation_event_id', $eventIds);
        });
    }

    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function count(): int
    {
        return $this->all()->count();
    }

    // @phpstan-ignore-next-line shipmonk.deadMethod
    public function verifiedCount(): int
    {
        return $this->baseQuery()
            ->whereHas('athleteRegistrations', function (Builder $registrations): void {
                $registrations->where('verified', true);
            })
            ->count();
    }

    protected function baseQuery(): Builder
    {
        return ExternalUser::query()
            ->select('external_users.*')
            ->whereHas('athleteRegistrations')
            ->distinct();
    }
}
