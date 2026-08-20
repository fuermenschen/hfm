<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Services\EventGroupMembershipService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** @api */
class RemoveEventGroupMemberAction
{
    public function __construct(public EventGroupMembershipService $eventGroupMembershipService) {}

    public function __invoke(EventGroup $eventGroup, AthleteRegistration $athleteRegistration, ExternalUser $externalUser): void
    {
        DB::transaction(function () use ($eventGroup, $athleteRegistration, $externalUser): void {
            $eventGroup = $this->eventGroupMembershipService->lockActiveGroup($eventGroup);
            $this->eventGroupMembershipService->acceptedAdmin($eventGroup, $externalUser);
            $member = $this->eventGroupMembershipService->groupRegistration($eventGroup, $athleteRegistration);

            if ($member->group_membership_status !== GroupMembershipStatus::Accepted) {
                throw ValidationException::withMessages(['group' => 'Dieses Gruppenmitglied gehört nicht zu dieser Gruppe.']);
            }

            if ($member->group_membership_role === GroupMembershipRole::Admin
                && ! $this->eventGroupMembershipService->hasOtherAcceptedAdmin($eventGroup, $member)) {
                throw ValidationException::withMessages(['group' => 'Die letzte Administratorin oder der letzte Administrator kann nicht entfernt werden.']);
            }

            $this->eventGroupMembershipService->clearMembership($member, GroupMembershipStatus::Accepted);
        });
    }
}
