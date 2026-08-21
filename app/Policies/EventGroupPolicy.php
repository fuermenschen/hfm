<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Models\User;

/** @api */
class EventGroupPolicy
{
    public function view(ExternalUser|User $externalUser, EventGroup $eventGroup): bool
    {
        if (! $externalUser instanceof ExternalUser) {
            return false;
        }

        return AthleteRegistration::query()
            ->where('donation_event_id', $eventGroup->donation_event_id)
            ->where('external_user_id', $externalUser->id)
            ->where('verified', true)
            ->exists();
    }

    public function viewPendingRequests(ExternalUser|User $externalUser, EventGroup $eventGroup): bool
    {
        return $this->isAcceptedAdmin($externalUser, $eventGroup);
    }

    public function processRequests(ExternalUser|User $externalUser, EventGroup $eventGroup): bool
    {
        return $this->isAcceptedAdmin($externalUser, $eventGroup);
    }

    public function removeMembers(ExternalUser|User $externalUser, EventGroup $eventGroup): bool
    {
        return $this->isAcceptedAdmin($externalUser, $eventGroup);
    }

    public function manageAdmins(ExternalUser|User $externalUser, EventGroup $eventGroup): bool
    {
        return $this->isAcceptedAdmin($externalUser, $eventGroup);
    }

    public function delete(ExternalUser|User $externalUser, EventGroup $eventGroup): bool
    {
        return $this->isAcceptedAdmin($externalUser, $eventGroup);
    }

    protected function isAcceptedAdmin(ExternalUser|User $externalUser, EventGroup $eventGroup): bool
    {
        if (! $externalUser instanceof ExternalUser) {
            return false;
        }

        return AthleteRegistration::query()
            ->where('donation_event_id', $eventGroup->donation_event_id)
            ->where('external_user_id', $externalUser->id)
            ->where('verified', true)
            ->whereBelongsTo($eventGroup)
            ->where('group_membership_status', GroupMembershipStatus::Accepted->value)
            ->where('group_membership_role', GroupMembershipRole::Admin->value)
            ->exists();
    }
}
