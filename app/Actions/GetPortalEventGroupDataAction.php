<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Services\DonationService;
use Illuminate\Database\Eloquent\Collection;

class GetPortalEventGroupDataAction
{
    public function __construct(public DonationService $donationService) {}

    /**
     * @return array{
     *     registration: AthleteRegistration,
     *     isAcceptedMember: bool,
     *     isAdmin: bool,
     *     canLeave: bool,
     *     acceptedCount: int,
     *     accepted: Collection<int, AthleteRegistration>,
     *     groupSummary: array{confirmedDonationCount: int, estimatedAmount: float, actualAmount: float, amount: float, amountLabel: string}|null,
     *     pending: Collection<int, AthleteRegistration>
     * }
     */
    public function __invoke(EventGroup $eventGroup, ExternalUser $externalUser): array
    {
        $eventGroup->loadMissing('donationEvent:id,slug,title,timezone,starts_at,ends_at,is_published');
        abort_unless($eventGroup->donationEvent->is_published, 404);

        $registration = AthleteRegistration::query()
            ->verifiedForEventUser($eventGroup->donationEvent, $externalUser)
            ->firstOrFail();
        $isAcceptedMember = $registration->event_group_id === $eventGroup->id
            && $registration->group_membership_status === GroupMembershipStatus::Accepted;
        $isAdmin = $isAcceptedMember && $registration->group_membership_role === GroupMembershipRole::Admin;
        $acceptedCount = $eventGroup->athleteRegistrations()
            ->where('verified', true)
            ->where('group_membership_status', GroupMembershipStatus::Accepted->value)
            ->count();

        if (! $isAcceptedMember) {
            return [
                'registration' => $registration,
                'isAcceptedMember' => false,
                'isAdmin' => false,
                'canLeave' => false,
                'acceptedCount' => $acceptedCount,
                'accepted' => collect(),
                'groupSummary' => null,
                'pending' => collect(),
            ];
        }

        $accepted = $eventGroup->athleteRegistrations()
            ->where('verified', true)
            ->where('group_membership_status', GroupMembershipStatus::Accepted->value)
            ->with([
                'externalUser' => fn ($query) => $query->select(['id', 'first_name', 'last_name', 'public_id']),
                'sportType:id,name',
                'donations' => fn ($query) => $query
                    ->where('verified', true)
                    ->select(['id', 'athlete_registration_id', 'amount_per_round', 'amount_min', 'amount_max']),
            ])
            ->get(['id', 'external_user_id', 'event_group_id', 'sport_type_id', 'rounds_estimated', 'rounds_done', 'group_membership_role']);

        foreach ($accepted as $member) {
            $member->donations->each(fn (Donation $donation): Donation => $donation->setRelation('athleteRegistration', $member));

            $memberDonationCount = $member->donations->count();
            $memberEstimatedAmount = $this->donationService->calculateEstimatedTotal($member->donations);
            $memberActualAmount = $this->donationService->calculateActualTotal($member->donations);

            $member->setAttribute('confirmed_donation_count', $memberDonationCount);
            $member->setAttribute('estimated_donation_amount', $memberEstimatedAmount);
            $member->setAttribute('actual_donation_amount', $memberActualAmount);

        }

        $canLeave = ! $eventGroup->donationEvent->hasEnded()
            && ($registration->group_membership_role !== GroupMembershipRole::Admin
                || $accepted->where('group_membership_role', GroupMembershipRole::Admin->value)->count() > 1);

        return [
            'registration' => $registration,
            'isAcceptedMember' => true,
            'isAdmin' => $isAdmin,
            'canLeave' => $canLeave,
            'acceptedCount' => $acceptedCount,
            'accepted' => $accepted,
            'groupSummary' => $this->summaryFromDonations($eventGroup, $accepted->flatMap->donations),
            'pending' => $isAdmin && ! $eventGroup->donationEvent->hasEnded()
                ? $eventGroup->athleteRegistrations()
                    ->where('group_membership_status', GroupMembershipStatus::Pending->value)
                    ->with(['externalUser' => fn ($query) => $query->select(['id', 'first_name', 'last_name', 'public_id'])])
                    ->get(['id', 'external_user_id', 'event_group_id'])
                : collect(),
        ];
    }

    /**
     * @return array{confirmedDonationCount: int, estimatedAmount: float, actualAmount: float, amount: float, amountLabel: string}
     */
    public function summary(EventGroup $eventGroup): array
    {
        $eventGroup->loadMissing('donationEvent:id,timezone,starts_at,ends_at');

        $donations = Donation::query()
            ->where('verified', true)
            ->whereHas('athleteRegistration', fn ($query) => $query
                ->where('event_group_id', $eventGroup->id)
                ->where('group_membership_status', GroupMembershipStatus::Accepted->value)
                ->where('verified', true))
            ->with('athleteRegistration:id,rounds_estimated,rounds_done')
            ->get(['id', 'athlete_registration_id', 'amount_per_round', 'amount_min', 'amount_max']);

        return $this->summaryFromDonations($eventGroup, $donations);
    }

    /**
     * @param  iterable<Donation>  $donations
     * @return array{confirmedDonationCount: int, estimatedAmount: float, actualAmount: float, amount: float, amountLabel: string}
     */
    protected function summaryFromDonations(EventGroup $eventGroup, iterable $donations): array
    {
        $donations = collect($donations);
        $estimatedAmount = $this->donationService->calculateEstimatedTotal($donations);
        $actualAmount = $this->donationService->calculateActualTotal($donations);
        $hasStarted = $eventGroup->donationEvent->hasStarted();

        return [
            'confirmedDonationCount' => $donations->count(),
            'estimatedAmount' => $estimatedAmount,
            'actualAmount' => $actualAmount,
            'amount' => $hasStarted ? $actualAmount : $estimatedAmount,
            'amountLabel' => $hasStarted ? 'Spenden (tatsächlich)' : 'Spenden (geschätzt)',
        ];
    }
}
