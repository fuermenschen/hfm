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

/** @api */
class PromoteEventGroupMemberAction
{
    public function __construct(public EventGroupMembershipService $eventGroupMembershipService) {}

    public function __invoke(EventGroup $eventGroup, AthleteRegistration $athleteRegistration, ExternalUser $externalUser): void
    {
        DB::transaction(function () use ($eventGroup, $athleteRegistration, $externalUser): void {
            $eventGroup = $this->eventGroupMembershipService->lockActiveGroup($eventGroup);
            $this->eventGroupMembershipService->acceptedAdmin($eventGroup, $externalUser);
            $member = $this->eventGroupMembershipService->groupRegistration($eventGroup, $athleteRegistration);

            if ($member->group_membership_status !== GroupMembershipStatus::Accepted
                || $member->group_membership_role !== GroupMembershipRole::Member) {
                return;
            }

            AthleteRegistration::query()
                ->whereKey($member)
                ->where('group_membership_status', GroupMembershipStatus::Accepted->value)
                ->where('group_membership_role', GroupMembershipRole::Member->value)
                ->update(['group_membership_role' => GroupMembershipRole::Admin->value]);
        });
    }
}
