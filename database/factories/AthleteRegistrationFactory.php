<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AthleteRegistration>
 */
class AthleteRegistrationFactory extends Factory
{
    protected $model = AthleteRegistration::class;

    public function definition(): array
    {
        return [
            'donation_event_id' => DonationEvent::factory(),
            'external_user_id' => ExternalUser::factory(),
            'sport_type_id' => fn () => SportType::query()->value('id')
                ?? SportType::query()->create(['name' => fake()->unique()->word()])->id,
            'partner_id' => null,
            'adult' => fake()->boolean(80),
            'rounds_estimated' => fake()->numberBetween(1, 10),
            'rounds_done' => fake()->numberBetween(0, 15),
            'comment' => fake()->optional()->text(2000),
            'verified' => fake()->boolean(80),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'verified' => true,
        ]);
    }

    public function forEvent(DonationEvent|int $event): static
    {
        $eventId = $event instanceof DonationEvent ? $event->id : $event;

        return $this->state(fn (): array => [
            'donation_event_id' => $eventId,
        ]);
    }

    public function forExternalUser(ExternalUser|int $externalUser): static
    {
        $externalUserId = $externalUser instanceof ExternalUser ? $externalUser->id : $externalUser;

        return $this->state(fn (): array => [
            'external_user_id' => $externalUserId,
        ]);
    }

    public function withPartner(Partner|int|null $partner = null): static
    {
        $partnerId = match (true) {
            $partner instanceof Partner => $partner->id,
            is_int($partner) => $partner,
            default => Partner::query()->inRandomOrder()->value('id'),
        };

        return $this->state(fn (): array => [
            'partner_id' => $partnerId,
        ]);
    }

    public function forVerifiedEventUser(DonationEvent|int $event, ExternalUser|int $externalUser): static
    {
        return $this
            ->forEvent($event)
            ->forExternalUser($externalUser)
            ->withPartner()
            ->verified();
    }
}
