<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Services\EventGroupMembershipService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** @api */
class LeaveEventGroupAction
{
    public function __construct(public EventGroupMembershipService $eventGroupMembershipService) {}

    public function __invoke(EventGroup $eventGroup, ExternalUser $externalUser): void
    {
        DB::transaction(function () use ($eventGroup, $externalUser): void {
            $eventGroup = $this->eventGroupMembershipService->lockActiveGroup($eventGroup);
            $registration = $this->eventGroupMembershipService->verifiedRegistration($eventGroup->donationEvent, $externalUser);

            if ($registration->event_group_id !== $eventGroup->id
                || $registration->group_membership_status !== GroupMembershipStatus::Accepted) {
                throw ValidationException::withMessages(['group' => 'Du bist kein Mitglied dieser Gruppe.']);
            }

            if ($registration->group_membership_role === GroupMembershipRole::Admin
                && ! $this->eventGroupMembershipService->hasOtherAcceptedAdmin($eventGroup, $registration)) {
                throw ValidationException::withMessages(['group' => 'Die letzte Administratorin oder der letzte Administrator kann die Gruppe nicht verlassen.']);
            }

            $this->eventGroupMembershipService->clearMembership($registration, GroupMembershipStatus::Accepted);
        });
    }
}
