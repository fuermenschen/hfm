<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Services\DonationService;
use Illuminate\Support\Collection;

class GetEventRankingsAction
{
    private const int LIMIT = 10;

    private const int METERS_PER_ROUND = 50;

    public function __construct(private DonationService $donationService) {}

    /**
     * Ranks athletes and groups by donations, rounds, and elevation. Donation
     * amounts include unverified donations, matching the results disclaimer.
     *
     * Registrations must have externalUser, eventGroup and donations loaded.
     *
     * @param  Collection<int, AthleteRegistration>  $registrations
     * @return array{athletes: array{donations: list<array{name: string, value: float|int}>, rounds: list<array{name: string, value: float|int}>, elevation_m: list<array{name: string, value: float|int}>}, groups: array{donations: list<array{name: string, value: float|int}>, rounds: list<array{name: string, value: float|int}>, elevation_m: list<array{name: string, value: float|int}>}}
     */
    public function __invoke(Collection $registrations): array
    {
        // Backfill the inverse relation so DonationService never queries
        // per donation.
        $registrations->each(function (AthleteRegistration $registration): void {
            $registration->donations->each(
                fn (Donation $donation): Donation => $donation->setRelation('athleteRegistration', $registration),
            );
        });

        $athletes = $registrations
            ->map(fn (AthleteRegistration $registration): array => [
                'name' => $registration->externalUser->privacy_name,
                'donations' => $this->donationService->calculateActualTotal($registration->donations),
                'rounds' => (int) $registration->rounds_done,
                'elevation_m' => (int) $registration->rounds_done * self::METERS_PER_ROUND,
            ]);

        $groups = $registrations
            ->filter(fn (AthleteRegistration $registration): bool => $registration->event_group_id !== null
                && $registration->group_membership_status === GroupMembershipStatus::Accepted)
            ->groupBy(fn (AthleteRegistration $registration): string => $registration->eventGroup->name)
            ->map(fn (Collection $members): array => [
                'name' => $members->first()->eventGroup->name,
                'donations' => $this->donationService->calculateActualTotal($members->flatMap->donations),
                'rounds' => (int) $members->sum('rounds_done'),
                'elevation_m' => (int) $members->sum('rounds_done') * self::METERS_PER_ROUND,
            ])
            ->values();

        return [
            'athletes' => $this->rankings($athletes),
            'groups' => $this->rankings($groups),
        ];
    }

    /**
     * @param  Collection<int, array{name: string, donations: float, rounds: int, elevation_m: int}>  $entries
     * @return array{donations: list<array{name: string, value: float|int}>, rounds: list<array{name: string, value: float|int}>, elevation_m: list<array{name: string, value: float|int}>}
     */
    protected function rankings(Collection $entries): array
    {
        return collect(['donations', 'rounds', 'elevation_m'])
            ->mapWithKeys(fn (string $metric): array => [$metric => $entries
                ->filter(fn (array $entry): bool => $entry[$metric] > 0)
                ->sort(fn (array $left, array $right): int => $right[$metric] <=> $left[$metric] ?: $left['name'] <=> $right['name'])
                ->take(self::LIMIT)
                ->map(fn (array $entry): array => ['name' => $entry['name'], 'value' => $entry[$metric]])
                ->values()
                ->all()])
            ->all();
    }
}
