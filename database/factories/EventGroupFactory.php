<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DonationEvent;
use App\Models\EventGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventGroup>
 */
class EventGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'donation_event_id' => DonationEvent::factory(),
            'name' => fake()->unique()->words(2, true),
        ];
    }

    public function forEvent(DonationEvent|int $event): static
    {
        return $this->state(fn (): array => [
            'donation_event_id' => $event instanceof DonationEvent ? $event->id : $event,
        ]);
    }
}
