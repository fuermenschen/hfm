<?php

use App\Actions\DemoteEventGroupAdminAction;
use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use Illuminate\Validation\ValidationException;

it('lets an admin demote another admin while retaining an admin', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $firstAdmin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $secondAdmin = AthleteRegistration::factory()->acceptedAdmin($group)->create();

    resolve(DemoteEventGroupAdminAction::class)($group, $secondAdmin, $firstAdmin->externalUser);

    $secondAdmin->refresh();
    $firstAdmin->refresh();

    expect($secondAdmin->event_group_id)->toBe($group->id)
        ->and($secondAdmin->group_membership_status)->toBe(GroupMembershipStatus::Accepted)
        ->and($secondAdmin->group_membership_role)->toBe(GroupMembershipRole::Member)
        ->and($firstAdmin->event_group_id)->toBe($group->id)
        ->and($firstAdmin->group_membership_status)->toBe(GroupMembershipStatus::Accepted)
        ->and($firstAdmin->group_membership_role)->toBe(GroupMembershipRole::Admin);
});

it('rejects self demotion', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $firstAdmin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    AthleteRegistration::factory()->acceptedAdmin($group)->create();

    expect(fn () => resolve(DemoteEventGroupAdminAction::class)($group, $firstAdmin, $firstAdmin->externalUser))
        ->toThrow(ValidationException::class);
});

it('blocks demotions after event end', function (): void {
    $event = eventGroupTestEvent(ended: true);
    $group = EventGroup::factory()->forEvent($event)->create();
    $firstAdmin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $secondAdmin = AthleteRegistration::factory()->acceptedAdmin($group)->create();

    expect(fn () => resolve(DemoteEventGroupAdminAction::class)($group, $secondAdmin, $firstAdmin->externalUser))
        ->toThrow(ValidationException::class);
});
