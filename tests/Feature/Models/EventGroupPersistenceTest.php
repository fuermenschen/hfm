<?php

use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\EventGroup;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

it('defines event group schema and indexes', function (): void {
    $eventGroupColumns = collect(Schema::getColumns('event_groups'))->keyBy('name');
    $registrationColumns = collect(Schema::getColumns('athlete_registrations'))->keyBy('name');
    $eventGroupIndexes = collect(Schema::getIndexes('event_groups'));
    $registrationIndexes = collect(Schema::getIndexes('athlete_registrations'));

    expect(Schema::hasColumns('event_groups', [
        'id',
        'donation_event_id',
        'name',
        'normalized_name',
        'created_at',
        'updated_at',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('athlete_registrations', [
            'event_group_id',
            'group_membership_status',
            'group_membership_role',
        ]))->toBeTrue()
        ->and($eventGroupColumns['donation_event_id']['nullable'])->toBeFalse()
        ->and($eventGroupColumns['name']['nullable'])->toBeFalse()
        ->and($eventGroupColumns['normalized_name']['nullable'])->toBeFalse()
        ->and($registrationColumns['event_group_id']['nullable'])->toBeTrue()
        ->and($registrationColumns['group_membership_status']['nullable'])->toBeTrue()
        ->and($registrationColumns['group_membership_role']['nullable'])->toBeTrue()
        ->and($eventGroupIndexes->contains(fn (array $index): bool => $index['unique']
            && $index['columns'] === ['donation_event_id', 'normalized_name']))->toBeTrue()
        ->and($registrationIndexes->contains(fn (array $index): bool => $index['name'] === 'athlete_registrations_group_membership_index'
            && $index['columns'] === ['event_group_id', 'group_membership_status']))->toBeTrue()
        ->and($registrationIndexes->contains(fn (array $index): bool => $index['unique']
            && $index['columns'] === ['donation_event_id', 'external_user_id']))->toBeTrue();
});

it('resolves event group relationships and enum casts', function (): void {
    $event = DonationEvent::factory()->create();
    $group = EventGroup::factory()->forEvent($event)->create(['name' => ' Team Blau ']);
    $pending = AthleteRegistration::factory()->pendingGroup($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();

    $pending->refresh();
    $member->refresh();
    $admin->refresh();

    expect($group->name)->toBe('Team Blau')
        ->and($group->normalized_name)->toBe('team blau')
        ->and($group->donationEvent->is($event))->toBeTrue()
        ->and($event->eventGroups)->toHaveCount(1)
        ->and($group->athleteRegistrations)->toHaveCount(3)
        ->and($pending->eventGroup->is($group))->toBeTrue()
        ->and($pending->donation_event_id)->toBe($event->id)
        ->and($member->donation_event_id)->toBe($event->id)
        ->and($admin->donation_event_id)->toBe($event->id)
        ->and($pending->group_membership_status)->toBe(GroupMembershipStatus::Pending)
        ->and($pending->group_membership_role)->toBeNull()
        ->and($member->group_membership_status)->toBe(GroupMembershipStatus::Accepted)
        ->and($member->group_membership_role)->toBe(GroupMembershipRole::Member)
        ->and($admin->group_membership_status)->toBe(GroupMembershipStatus::Accepted)
        ->and($admin->group_membership_role)->toBe(GroupMembershipRole::Admin);
});

it('creates only valid group membership column combinations', function (): void {
    $event = DonationEvent::factory()->create();
    $group = EventGroup::factory()->forEvent($event)->create();
    $registration = AthleteRegistration::factory()->forEvent($event)->create();
    $pending = AthleteRegistration::factory()->pendingGroup($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();

    expect([
        [$registration->event_group_id, $registration->group_membership_status, $registration->group_membership_role],
        [$pending->event_group_id, $pending->group_membership_status, $pending->group_membership_role],
        [$member->event_group_id, $member->group_membership_status, $member->group_membership_role],
        [$admin->event_group_id, $admin->group_membership_status, $admin->group_membership_role],
    ])->toBe([
        [null, null, null],
        [$group->id, GroupMembershipStatus::Pending, null],
        [$group->id, GroupMembershipStatus::Accepted, GroupMembershipRole::Member],
        [$group->id, GroupMembershipStatus::Accepted, GroupMembershipRole::Admin],
    ]);
});

it('keeps factory group memberships on the group event', function (): void {
    $groupEvent = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();
    $group = EventGroup::factory()->forEvent($groupEvent)->create();

    $registration = AthleteRegistration::factory()
        ->acceptedMember($group)
        ->forEvent($otherEvent)
        ->create();

    expect($registration->event_group_id)->toBe($group->id)
        ->and($registration->donation_event_id)->toBe($groupEvent->id)
        ->and($registration->group_membership_status)->toBe(GroupMembershipStatus::Accepted)
        ->and($registration->group_membership_role)->toBe(GroupMembershipRole::Member);
});

it('keeps group names unique within an event after normalization', function (): void {
    $event = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();

    EventGroup::factory()->forEvent($event)->create(['name' => ' Team Blau ']);

    expect(fn () => EventGroup::factory()->forEvent($event)->create(['name' => 'team blau']))
        ->toThrow(QueryException::class);

    $unicodeGroup = EventGroup::factory()->forEvent($event)->create(['name' => ' Überflieger ']);

    expect($unicodeGroup->name)->toBe('Überflieger')
        ->and($unicodeGroup->normalized_name)->toBe('uberflieger')
        ->and(fn () => EventGroup::factory()->forEvent($event)->create(['name' => 'uberflieger']))
        ->toThrow(QueryException::class);

    $otherGroup = EventGroup::factory()->forEvent($otherEvent)->create(['name' => 'team blau']);

    expect($otherGroup->donation_event_id)->toBe($otherEvent->id);
});

it('restricts deleting referenced events and groups', function (): void {
    $event = DonationEvent::factory()->create();
    $group = EventGroup::factory()->forEvent($event)->create();

    expect(fn () => $event->delete())->toThrow(QueryException::class);

    $registration = AthleteRegistration::factory()->acceptedMember($group)->create();

    expect(fn () => $group->delete())->toThrow(QueryException::class)
        ->and($registration->exists)->toBeTrue();
});

it('preserves one registration per external user and event', function (): void {
    $event = DonationEvent::factory()->create();
    $registration = AthleteRegistration::factory()->forEvent($event)->create();

    expect($registration->event_group_id)->toBeNull()
        ->and($registration->group_membership_status)->toBeNull()
        ->and($registration->group_membership_role)->toBeNull()
        ->and(fn () => AthleteRegistration::factory()
            ->forEvent($event)
            ->forExternalUser($registration->externalUser)
            ->create())->toThrow(QueryException::class);
});
