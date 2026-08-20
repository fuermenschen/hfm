<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GroupMembershipStatus;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Services\EventGroupMembershipService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** @api */
class DeleteEventGroupAction
{
    public function __construct(public EventGroupMembershipService $eventGroupMembershipService) {}

    public function __invoke(EventGroup $eventGroup, ExternalUser $externalUser): void
    {
        DB::transaction(function () use ($eventGroup, $externalUser): void {
            $eventGroup = $this->eventGroupMembershipService->lockActiveGroup($eventGroup);
            $admin = $this->eventGroupMembershipService->acceptedAdmin($eventGroup, $externalUser);

            if (! $this->eventGroupMembershipService->isOnlyRegistration($eventGroup, $admin)) {
                throw ValidationException::withMessages(['group' => 'Diese Gruppe kann nur ohne weitere Mitglieder oder offene Anfragen gelöscht werden.']);
            }

            $this->eventGroupMembershipService->clearMembership($admin, GroupMembershipStatus::Accepted);

            $eventGroup->delete();
        });
    }
}
