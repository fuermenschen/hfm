<?php

use App\Actions\CreateEventGroupAction;
use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\EventGroup;
use Illuminate\Validation\ValidationException;

it('creates a group and makes its verified creator the first admin', function (): void {
    $event = eventGroupTestEvent();
    $registration = eventGroupTestRegistration($event);

    $group = resolve(CreateEventGroupAction::class)($event, $registration->externalUser, ' Team Blau ');

    $registration->refresh();

    expect($group->name)->toBe('Team Blau')
        ->and($group->donationEvent->is($event))->toBeTrue()
        ->and($registration->eventGroup->is($group))->toBeTrue()
        ->and($registration->group_membership_status)->toBe(GroupMembershipStatus::Accepted)
        ->and($registration->group_membership_role)->toBe(GroupMembershipRole::Admin);
});

it('rejects creation after event end, for unverified registrations, and occupied slots', function (): void {
    $endedEvent = eventGroupTestEvent(ended: true);
    $endedRegistration = eventGroupTestRegistration($endedEvent);
    $unverifiedEvent = eventGroupTestEvent();
    $unverifiedRegistration = eventGroupTestRegistration($unverifiedEvent);
    $unverifiedRegistration->update(['verified' => false]);
    $occupiedEvent = eventGroupTestEvent();
    $occupiedRegistration = eventGroupTestRegistration($occupiedEvent);
    $occupiedGroup = EventGroup::factory()->forEvent($occupiedEvent)->create();
    $occupiedRegistration->update([
        'event_group_id' => $occupiedGroup->id,
        'group_membership_status' => GroupMembershipStatus::Pending,
    ]);
    $action = resolve(CreateEventGroupAction::class);

    expect(fn () => $action($endedEvent, $endedRegistration->externalUser, 'Spät'))->toThrow(ValidationException::class)
        ->and(fn () => $action($unverifiedEvent, $unverifiedRegistration->externalUser, 'Ohne Bestätigung'))->toThrow(ValidationException::class)
        ->and(fn () => $action($occupiedEvent, $occupiedRegistration->externalUser, 'Schon dabei'))->toThrow(ValidationException::class);
});

it('keeps names unique within an event', function (): void {
    $event = eventGroupTestEvent();
    $firstRegistration = eventGroupTestRegistration($event);
    $secondRegistration = eventGroupTestRegistration($event);
    $action = resolve(CreateEventGroupAction::class);

    $action($event, $firstRegistration->externalUser, 'Team Blau');

    expect(fn () => $action($event, $secondRegistration->externalUser, 'team blau'))->toThrow(ValidationException::class)
        ->and(EventGroup::query()->whereBelongsTo($event)->count())->toBe(1);
});
