<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Notifications\EventGroupMembershipAccepted;
use App\Services\EventGroupMembershipService;
use Illuminate\Support\Facades\DB;

/** @api */
class AcceptEventGroupMembershipRequestAction
{
    public function __construct(public EventGroupMembershipService $eventGroupMembershipService) {}

    public function __invoke(EventGroup $eventGroup, AthleteRegistration $athleteRegistration, ExternalUser $externalUser): void
    {
        $applicant = DB::transaction(function () use (&$eventGroup, $athleteRegistration, $externalUser): ?AthleteRegistration {
            $eventGroup = $this->eventGroupMembershipService->lockActiveGroup($eventGroup);
            $this->eventGroupMembershipService->acceptedAdmin($eventGroup, $externalUser);
            $applicant = $this->eventGroupMembershipService->groupRegistration($eventGroup, $athleteRegistration);

            if ($applicant->group_membership_status !== GroupMembershipStatus::Pending) {
                return null;
            }

            return $this->accept($eventGroup, $applicant) ? $applicant : null;
        });

        if (! $applicant instanceof AthleteRegistration) {
            return;
        }

        $applicant->loadMissing('externalUser');
        $applicant->externalUser->notify(new EventGroupMembershipAccepted(
            firstName: $applicant->externalUser->first_name,
            groupName: $eventGroup->name,
            eventTitle: $eventGroup->donationEvent->title,
        ));
    }

    protected function accept(EventGroup $eventGroup, AthleteRegistration $applicant): bool
    {
        return AthleteRegistration::query()
            ->whereKey($applicant)
            ->whereBelongsTo($eventGroup)
            ->where('group_membership_status', GroupMembershipStatus::Pending->value)
            ->update([
                'group_membership_status' => GroupMembershipStatus::Accepted->value,
                'group_membership_role' => GroupMembershipRole::Member->value,
            ]) === 1;
    }
}
