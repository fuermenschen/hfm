<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GroupMembershipStatus;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Services\EventGroupMembershipService;
use Illuminate\Support\Facades\DB;

/** @api */
class WithdrawEventGroupMembershipRequestAction
{
    public function __construct(public EventGroupMembershipService $eventGroupMembershipService) {}

    public function __invoke(EventGroup $eventGroup, ExternalUser $externalUser): void
    {
        DB::transaction(function () use ($eventGroup, $externalUser): void {
            $eventGroup = $this->eventGroupMembershipService->lockActiveGroup($eventGroup);
            $registration = $this->eventGroupMembershipService->verifiedRegistration($eventGroup->donationEvent, $externalUser);

            if ($registration->event_group_id === $eventGroup->id) {
                $this->eventGroupMembershipService->clearMembership($registration, GroupMembershipStatus::Pending);
            }
        });
    }
}
