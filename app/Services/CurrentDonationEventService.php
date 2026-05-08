<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DonationEvent;
use App\Settings\EventSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CurrentDonationEventService
{
    public function current(): ?DonationEvent
    {
        /** @var array{event: ?DonationEvent, issue: ?string} $resolved */
        $resolved = $this->resolve();

        return $resolved['event'];
    }

    public function issue(): ?string
    {
        /** @var array{event: ?DonationEvent, issue: ?string} $resolved */
        $resolved = $this->resolve();

        return $resolved['issue'];
    }

    /**
     * @return array{event: ?DonationEvent, issue: ?string}
     */
    protected function resolve(): array
    {
        return once(function (): array {
            /** @var array{event_id: ?int, issue: ?string} $cached */
            $cached = Cache::remember('current_donation_event', now()->addMinute(), function (): array {
                if (! Schema::hasTable('donation_events')) {
                    return ['event_id' => null, 'issue' => 'missing_events_table'];
                }

                $eventId = resolve(EventSettings::class)->current_event_id;

                if (! is_int($eventId) || $eventId < 1) {
                    return ['event_id' => null, 'issue' => 'missing_current_event'];
                }

                $event = DonationEvent::query()->find($eventId);

                if (! $event instanceof DonationEvent) {
                    return ['event_id' => null, 'issue' => 'current_event_not_found'];
                }

                if (! $event->is_published) {
                    return ['event_id' => null, 'issue' => 'current_event_unpublished'];
                }

                return ['event_id' => $event->id, 'issue' => null];
            });

            $event = is_int($cached['event_id'])
                ? DonationEvent::query()->find($cached['event_id'])
                : null;

            return [
                'event' => $event instanceof DonationEvent ? $event : null,
                'issue' => $cached['issue'],
            ];
        });
    }
}
