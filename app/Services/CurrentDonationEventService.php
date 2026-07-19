<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DonationEvent;
use App\Settings\EventSettings;
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
            if (! Schema::hasTable('donation_events')) {
                return ['event' => null, 'issue' => 'missing_events_table'];
            }

            $eventId = resolve(EventSettings::class)->current_event_id;

            if (! is_int($eventId) || $eventId < 1) {
                return ['event' => null, 'issue' => 'missing_current_event'];
            }

            $event = DonationEvent::query()->find($eventId);

            if (! $event instanceof DonationEvent) {
                return ['event' => null, 'issue' => 'current_event_not_found'];
            }

            if (! $event->is_published) {
                return ['event' => null, 'issue' => 'current_event_unpublished'];
            }

            return ['event' => $event, 'issue' => null];
        });
    }
}
