<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class EventGroupMembershipService
{
    public function lockActiveDonationEvent(DonationEvent $donationEvent): DonationEvent
    {
        $donationEvent = DonationEvent::query()->whereKey($donationEvent)->lockForUpdate()->firstOrFail();

        if ($donationEvent->hasEnded()) {
            throw ValidationException::withMessages(['group' => 'Gruppen können nach Ende des Anlasses nicht mehr geändert werden.']);
        }

        return $donationEvent;
    }

    public function lockActiveGroup(EventGroup $eventGroup): EventGroup
    {
        $eventGroup = EventGroup::query()
            ->with('donationEvent')
            ->whereKey($eventGroup)
            ->lockForUpdate()
            ->firstOrFail();

        $this->lockActiveDonationEvent($eventGroup->donationEvent);

        return $eventGroup;
    }

    public function verifiedRegistration(DonationEvent $donationEvent, ExternalUser $externalUser): AthleteRegistration
    {
        $registration = AthleteRegistration::query()
            ->verifiedForEventUser($donationEvent, $externalUser)
            ->lockForUpdate()
            ->first();

        if (! $registration instanceof AthleteRegistration) {
            throw ValidationException::withMessages(['group' => 'Du brauchst eine bestätigte Sportler:innen-Anmeldung für diesen Anlass.']);
        }

        return $registration;
    }

    public function acceptedAdmin(EventGroup $eventGroup, ExternalUser $externalUser): AthleteRegistration
    {
        $registration = $eventGroup->acceptedAdmins()
            ->whereBelongsTo($externalUser)
            ->where('donation_event_id', $eventGroup->donation_event_id)
            ->lockForUpdate()
            ->first();

        throw_unless($registration instanceof AthleteRegistration, AuthorizationException::class, 'Du bist keine:r Administrator:in dieser Gruppe.');

        return $registration;
    }

    public function groupRegistration(EventGroup $eventGroup, AthleteRegistration $athleteRegistration): AthleteRegistration
    {
        $registration = $this->lockedRegistration($athleteRegistration);

        if ($registration->donation_event_id !== $eventGroup->donation_event_id
            || $registration->event_group_id !== $eventGroup->id
            || ! $registration->verified) {
            throw ValidationException::withMessages(['group' => 'Diese Anmeldung gehört nicht zu dieser Gruppe.']);
        }

        return $registration;
    }

    public function lockedRegistration(AthleteRegistration $athleteRegistration): AthleteRegistration
    {
        $registration = AthleteRegistration::query()->whereKey($athleteRegistration)->lockForUpdate()->first();

        throw_unless($registration instanceof AthleteRegistration, ValidationException::class, 'Diese Anmeldung existiert nicht mehr.');

        return $registration;
    }

    public function hasOtherAcceptedAdmin(EventGroup $eventGroup, AthleteRegistration $athleteRegistration): bool
    {
        return $eventGroup->acceptedAdmins()
            ->whereKeyNot($athleteRegistration)
            ->lockForUpdate()
            ->exists();
    }

    public function clearMembership(AthleteRegistration $athleteRegistration, GroupMembershipStatus $expectedStatus): bool
    {
        return AthleteRegistration::query()
            ->whereKey($athleteRegistration)
            ->where('group_membership_status', $expectedStatus->value)
            ->update([
                'event_group_id' => null,
                'group_membership_status' => null,
                'group_membership_role' => null,
            ]) === 1;
    }

    public function isOnlyRegistration(EventGroup $eventGroup, AthleteRegistration $athleteRegistration): bool
    {
        $registrations = $eventGroup->athleteRegistrations()->lockForUpdate()->get();

        return $registrations->count() === 1 && $registrations->first()?->is($athleteRegistration) === true;
    }
}
