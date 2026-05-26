<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\DonationEvent;

class DonationEventIdParser
{
    /**
     * @param  iterable<mixed>  $events
     * @return array<int, int>
     */
    public function __invoke(iterable $events): array
    {
        $eventIds = [];

        foreach ($events as $event) {
            if ($event instanceof DonationEvent) {
                $eventIds[] = $event->id;

                continue;
            }

            if (is_int($event)) {
                $eventIds[] = $event;

                continue;
            }

            if (is_string($event) && is_numeric($event)) {
                $eventIds[] = (int) $event;
            }
        }

        return array_values(array_unique(array_filter($eventIds, fn (int $eventId): bool => $eventId > 0)));
    }
}
