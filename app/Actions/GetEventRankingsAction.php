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

    public function __construct(private DonationService $donationService) {}

    /**
     * Ranks athletes and groups of an event by actual donation amounts.
     * Amounts include unverified donations, matching the results page totals
     * and their disclaimer. Only entries with donations are ranked.
     *
     * Registrations must have externalUser, eventGroup and donations loaded.
     *
     * @param  Collection<int, AthleteRegistration>  $registrations
     * @return array{athletes: list<array{name: string, amount: float}>, groups: list<array{name: string, amount: float}>}
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
                'amount' => $this->donationService->calculateActualTotal($registration->donations),
            ])
            ->filter(fn (array $entry): bool => $entry['amount'] > 0.0)
            ->sortByDesc('amount')
            ->take(self::LIMIT)
            ->values()
            ->all();

        $groups = $registrations
            ->filter(fn (AthleteRegistration $registration): bool => $registration->event_group_id !== null
                && $registration->group_membership_status === GroupMembershipStatus::Accepted)
            ->groupBy(fn (AthleteRegistration $registration): string => $registration->eventGroup->name)
            ->map(fn (Collection $members): array => [
                'name' => $members->first()->eventGroup->name,
                'amount' => $this->donationService->calculateActualTotal($members->flatMap->donations),
            ])
            ->filter(fn (array $entry): bool => $entry['amount'] > 0.0)
            ->sortByDesc('amount')
            ->take(self::LIMIT)
            ->values()
            ->all();

        return ['athletes' => $athletes, 'groups' => $groups];
    }
}
