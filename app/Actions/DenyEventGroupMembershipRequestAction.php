<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Notifications\EventGroupMembershipDenied;
use App\Services\EventGroupMembershipService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** @api */
class DenyEventGroupMembershipRequestAction
{
    public function __construct(public EventGroupMembershipService $eventGroupMembershipService) {}

    public function __invoke(EventGroup $eventGroup, AthleteRegistration $athleteRegistration, ExternalUser $externalUser): void
    {
        $applicant = DB::transaction(function () use ($eventGroup, $athleteRegistration, $externalUser): ?AthleteRegistration {
            $eventGroup = $this->eventGroupMembershipService->lockActiveGroup($eventGroup);
            $this->eventGroupMembershipService->acceptedAdmin($eventGroup, $externalUser);
            $applicant = $this->eventGroupMembershipService->lockedRegistration($athleteRegistration);

            if ($applicant->donation_event_id !== $eventGroup->donation_event_id || ! $applicant->verified) {
                throw ValidationException::withMessages(['group' => 'Diese Gruppenanfrage gehört nicht zu diesem Anlass.']);
            }

            if ($applicant->event_group_id === null
                && $applicant->group_membership_status === null
                && $applicant->group_membership_role === null) {
                return null;
            }

            if ($applicant->event_group_id !== $eventGroup->id) {
                throw ValidationException::withMessages(['group' => 'Diese Gruppenanfrage gehört nicht zu diesem Anlass.']);
            }

            $this->eventGroupMembershipService->groupRegistration($eventGroup, $applicant);

            if ($applicant->group_membership_status !== GroupMembershipStatus::Pending) {
                return null;
            }

            return $this->eventGroupMembershipService->clearMembership($applicant, GroupMembershipStatus::Pending) ? $applicant : null;
        });

        if (! $applicant instanceof AthleteRegistration) {
            return;
        }

        $applicant->loadMissing('externalUser');
        $applicant->externalUser->notify(new EventGroupMembershipDenied(
            firstName: $applicant->externalUser->first_name,
            groupName: $eventGroup->name,
            eventTitle: $eventGroup->donationEvent->title,
        ));
    }
}
