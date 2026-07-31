<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Support\DonationEventIdParser;
use Illuminate\Database\Eloquent\Builder;

class DonorService
{
    public function __construct(private DonationEventIdParser $donationEventIdParser) {}

    public function all(): Builder
    {
        return $this->baseQuery();
    }

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

        return $this->baseQuery()->whereHas('donationsAsDonor', function (Builder $donations) use ($eventIds): void {
            $donations->whereHas('athleteRegistration', function (Builder $registration) use ($eventIds): void {
                $registration->whereIn('donation_event_id', $eventIds);
            });
        });
    }

    // TODO(refactor-external-user): Replace legacy donor counts with DonorService::count in dashboard/reporting.
    public function count(): int
    {
        return (int) $this->all()->count();
    }

    protected function baseQuery(): Builder
    {
        return ExternalUser::query()
            ->select('external_users.*')
            ->whereHas('donationsAsDonor')
            ->distinct();
    }
}
