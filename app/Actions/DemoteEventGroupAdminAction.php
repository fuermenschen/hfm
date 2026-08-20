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
class DemoteEventGroupAdminAction
{
    public function __construct(public EventGroupMembershipService $eventGroupMembershipService) {}

    public function __invoke(EventGroup $eventGroup, AthleteRegistration $athleteRegistration, ExternalUser $externalUser): void
    {
        DB::transaction(function () use ($eventGroup, $athleteRegistration, $externalUser): void {
            $eventGroup = $this->eventGroupMembershipService->lockActiveGroup($eventGroup);
            $actingAdmin = $this->eventGroupMembershipService->acceptedAdmin($eventGroup, $externalUser);
            $admin = $this->eventGroupMembershipService->groupRegistration($eventGroup, $athleteRegistration);

            if ($admin->is($actingAdmin)) {
                throw ValidationException::withMessages(['group' => 'Du kannst dich nicht selbst herabstufen.']);
            }

            if ($admin->group_membership_status !== GroupMembershipStatus::Accepted
                || $admin->group_membership_role !== GroupMembershipRole::Admin) {
                return;
            }

            if (! $this->eventGroupMembershipService->hasOtherAcceptedAdmin($eventGroup, $admin)) {
                throw ValidationException::withMessages(['group' => 'Die letzte Administratorin oder der letzte Administrator kann nicht herabgestuft werden.']);
            }

            AthleteRegistration::query()
                ->whereKey($admin)
                ->where('group_membership_status', GroupMembershipStatus::Accepted->value)
                ->where('group_membership_role', GroupMembershipRole::Admin->value)
                ->update(['group_membership_role' => GroupMembershipRole::Member->value]);
        });
    }
}
