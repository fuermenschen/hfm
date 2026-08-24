<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Notifications\EventGroupMemberLeft;
use App\Services\EventGroupMembershipService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** @api */
class LeaveEventGroupAction
{
    public function __construct(public EventGroupMembershipService $eventGroupMembershipService) {}

    public function __invoke(EventGroup $eventGroup, ExternalUser $externalUser): void
    {
        /** @var list<ExternalUser> $recipients */
        $recipients = DB::transaction(function () use (&$eventGroup, $externalUser): array {
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

            $recipients = $eventGroup->acceptedAdmins()
                ->whereKeyNot($registration)
                ->with('externalUser')
                ->get()
                ->map(fn (AthleteRegistration $admin): ExternalUser => $admin->externalUser)
                ->all();

            return $this->eventGroupMembershipService->clearMembership($registration, GroupMembershipStatus::Accepted)
                ? $recipients
                : [];
        });

        foreach ($recipients as $recipient) {
            $recipient->notify(new EventGroupMemberLeft(
                firstName: $recipient->first_name,
                memberName: $externalUser->privacy_name,
                groupName: $eventGroup->name,
                eventTitle: $eventGroup->donationEvent->title,
                eventGroupId: $eventGroup->id,
            ));
        }
    }
}
