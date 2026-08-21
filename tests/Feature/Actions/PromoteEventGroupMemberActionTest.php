<?php

use App\Actions\PromoteEventGroupMemberAction;
use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

it('lets an admin promote an accepted member', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();

    resolve(PromoteEventGroupMemberAction::class)($group, $member, $admin->externalUser);

    $member->refresh();

    expect($member->event_group_id)->toBe($group->id)
        ->and($member->group_membership_status)->toBe(GroupMembershipStatus::Accepted)
        ->and($member->group_membership_role)->toBe(GroupMembershipRole::Admin);
});

it('rejects promotions by non-admins', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();

    expect(fn () => resolve(PromoteEventGroupMemberAction::class)($group, $admin, $member->externalUser))
        ->toThrow(AuthorizationException::class);
});

it('blocks promotions after event end', function (): void {
    $event = eventGroupTestEvent(ended: true);
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();

    expect(fn () => resolve(PromoteEventGroupMemberAction::class)($group, $member, $admin->externalUser))
        ->toThrow(ValidationException::class);
});
