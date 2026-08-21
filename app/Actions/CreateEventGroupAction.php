<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Services\EventGroupMembershipService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** @api */
class CreateEventGroupAction
{
    public function __construct(public EventGroupMembershipService $eventGroupMembershipService) {}

    public function __invoke(DonationEvent $donationEvent, ExternalUser $externalUser, string $name): EventGroup
    {
        $name = trim($name);

        if ($name === '' || Str::length($name) > 255) {
            throw ValidationException::withMessages(['name' => 'Bitte gib einen gültigen Gruppennamen ein.']);
        }

        try {
            return DB::transaction(function () use ($donationEvent, $externalUser, $name): EventGroup {
                $donationEvent = $this->eventGroupMembershipService->lockActiveDonationEvent($donationEvent);
                $registration = $this->eventGroupMembershipService->verifiedRegistration($donationEvent, $externalUser);

                if ($registration->hasGroupMembership()) {
                    throw ValidationException::withMessages(['group' => 'Du bist bereits Mitglied einer Gruppe oder hast eine offene Anfrage.']);
                }

                $eventGroup = EventGroup::query()->create([
                    'donation_event_id' => $donationEvent->id,
                    'name' => $name,
                ]);

                if (! $this->assignCreator($eventGroup, $registration)) {
                    throw ValidationException::withMessages(['group' => 'Du bist bereits Mitglied einer Gruppe oder hast eine offene Anfrage.']);
                }

                return $eventGroup;
            });
        } catch (QueryException $queryException) {
            if ($queryException->getCode() === '23000') {
                throw ValidationException::withMessages(['name' => 'Dieser Gruppenname ist für diesen Anlass bereits vergeben.']);
            }

            throw $queryException;
        }
    }

    protected function assignCreator(EventGroup $eventGroup, AthleteRegistration $registration): bool
    {
        return AthleteRegistration::query()
            ->whereKey($registration)
            ->whereNull('event_group_id')
            ->whereNull('group_membership_status')
            ->whereNull('group_membership_role')
            ->update([
                'event_group_id' => $eventGroup->id,
                'group_membership_status' => GroupMembershipStatus::Accepted->value,
                'group_membership_role' => GroupMembershipRole::Admin->value,
            ]) === 1;
    }
}
