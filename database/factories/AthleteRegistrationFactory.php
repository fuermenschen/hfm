<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventState;
use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\EventGroup;
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
            'event_group_id' => null,
            'group_membership_status' => null,
            'group_membership_role' => null,
            'adult' => fake()->boolean(80),
            'rounds_estimated' => fake()->numberBetween(1, 10),
            'rounds_done' => fake()->numberBetween(0, 15),
            'event_state' => EventState::NotStarted->value,
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

    public function withStartNumber(int $startNumber): static
    {
        return $this->state(fn (array $attributes): array => [
            'start_number' => $startNumber,
        ]);
    }

    public function running(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_state' => EventState::Running->value,
        ]);
    }

    public function finished(): static
    {
        return $this->state(fn (array $attributes): array => [
            'event_state' => EventState::Finished->value,
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

    protected function forGroup(EventGroup|int $group): static
    {
        $eventGroup = $group instanceof EventGroup
            ? $group
            : EventGroup::query()->findOrFail($group);

        return $this->state(fn (): array => [
            'event_group_id' => $eventGroup->id,
        ])->afterMaking(function (AthleteRegistration $registration) use ($eventGroup): void {
            $registration->event_group_id = $eventGroup->id;
            $registration->donation_event_id = $eventGroup->donation_event_id;
        });
    }

    public function pendingGroup(EventGroup|int $group): static
    {
        return $this
            ->forGroup($group)
            ->verified()
            ->state([
                'group_membership_status' => GroupMembershipStatus::Pending->value,
                'group_membership_role' => null,
            ]);
    }

    public function acceptedMember(EventGroup|int $group): static
    {
        return $this
            ->forGroup($group)
            ->verified()
            ->state([
                'group_membership_status' => GroupMembershipStatus::Accepted->value,
                'group_membership_role' => GroupMembershipRole::Member->value,
            ]);
    }

    public function acceptedAdmin(EventGroup|int $group): static
    {
        return $this
            ->forGroup($group)
            ->verified()
            ->state([
                'group_membership_status' => GroupMembershipStatus::Accepted->value,
                'group_membership_role' => GroupMembershipRole::Admin->value,
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
