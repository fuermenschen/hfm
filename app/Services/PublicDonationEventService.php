<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PublicEventLifecycle;
use App\Models\DonationEvent;
use Illuminate\Support\Collection;

class PublicDonationEventService
{
    public function lifecycle(DonationEvent $event): PublicEventLifecycle
    {
        if ($event->hasEnded()) {
            return PublicEventLifecycle::Completed;
        }

        return $event->hasStarted() ? PublicEventLifecycle::Live : PublicEventLifecycle::Upcoming;
    }

    /**
     * @return array{
     *     live: ?DonationEvent,
     *     upcoming: ?DonationEvent,
     *     completed: ?DonationEvent,
     *     historical: Collection<int, DonationEvent>,
     * }
     */
    public function resolve(): array
    {
        $eventsByLifecycle = DonationEvent::query()
            ->where('is_published', true)
            ->oldest('starts_at')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (DonationEvent $event): string => $this->lifecycle($event)->value);

        $historical = $eventsByLifecycle->get(PublicEventLifecycle::Completed->value, collect())
            ->sortBy([['ends_at', 'desc'], ['id', 'asc']])
            ->values();

        return [
            'live' => $eventsByLifecycle->get(PublicEventLifecycle::Live->value)?->first(),
            'upcoming' => $eventsByLifecycle->get(PublicEventLifecycle::Upcoming->value)?->first(),
            'completed' => $historical->first(),
            'historical' => $historical,
        ];
    }

    /**
     * Public page entrypoint; production integration follows in step 4 of #264.
     *
     * @api
     */
    public function homepage(): ?DonationEvent
    {
        $events = $this->resolve();

        return $events['live'] ?? $events['upcoming'] ?? $events['completed'];
    }

    public function results(): ?DonationEvent
    {
        $events = $this->resolve();

        return $events['live'] ?? $events['completed'] ?? $events['upcoming'];
    }
}
