<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Notifications\EventGroupMembershipRequested;
use App\Services\EventGroupMembershipService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** @api */
class RequestEventGroupMembershipAction
{
    public function __construct(public EventGroupMembershipService $eventGroupMembershipService) {}

    public function __invoke(EventGroup $eventGroup, ExternalUser $externalUser): void
    {
        /** @var list<ExternalUser> $recipients */
        $recipients = DB::transaction(function () use (&$eventGroup, $externalUser): array {
            $eventGroup = $this->eventGroupMembershipService->lockActiveGroup($eventGroup);
            $registration = $this->eventGroupMembershipService->verifiedRegistration($eventGroup->donationEvent, $externalUser);

            if ($registration->hasGroupMembership()) {
                throw ValidationException::withMessages(['group' => 'Du bist bereits Mitglied einer Gruppe oder hast eine offene Anfrage.']);
            }

            if (! $this->requestMembership($eventGroup, $registration)) {
                return [];
            }

            return $eventGroup->acceptedAdmins()
                ->with('externalUser')
                ->get()
                ->map(fn (AthleteRegistration $admin): ExternalUser => $admin->externalUser)
                ->all();
        });

        foreach ($recipients as $recipient) {
            $recipient->notify(new EventGroupMembershipRequested(
                firstName: $recipient->first_name,
                groupName: $eventGroup->name,
                eventTitle: $eventGroup->donationEvent->title,
                applicantPrivacyName: $externalUser->privacy_name,
            ));
        }
    }

    protected function requestMembership(EventGroup $eventGroup, AthleteRegistration $registration): bool
    {
        return AthleteRegistration::query()
            ->whereKey($registration)
            ->whereNull('event_group_id')
            ->whereNull('group_membership_status')
            ->whereNull('group_membership_role')
            ->update([
                'event_group_id' => $eventGroup->id,
                'group_membership_status' => GroupMembershipStatus::Pending->value,
                'group_membership_role' => null,
            ]) === 1;
    }
}
