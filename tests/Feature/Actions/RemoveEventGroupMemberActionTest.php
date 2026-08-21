<?php

use App\Actions\RemoveEventGroupMemberAction;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

it('lets an admin remove accepted members', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();

    resolve(RemoveEventGroupMemberAction::class)($group, $member, $admin->externalUser);

    $member->refresh();

    expect($member->event_group_id)->toBeNull()
        ->and($member->group_membership_status)->toBeNull()
        ->and($member->group_membership_role)->toBeNull();
});

it('rejects non-admin removals and removing the last admin', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();
    $action = resolve(RemoveEventGroupMemberAction::class);

    expect(fn () => $action($group, $admin, $member->externalUser))->toThrow(AuthorizationException::class)
        ->and(fn () => $action($group, $admin, $admin->externalUser))->toThrow(ValidationException::class);
});

it('blocks removals after event end', function (): void {
    $event = eventGroupTestEvent(ended: true);
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();

    expect(fn () => resolve(RemoveEventGroupMemberAction::class)($group, $member, $admin->externalUser))
        ->toThrow(ValidationException::class);
});
