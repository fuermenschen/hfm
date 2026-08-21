<?php

use App\Actions\DeleteEventGroupAction;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use Illuminate\Validation\ValidationException;

it('deletes a group with only its sole admin and clears that membership', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();

    resolve(DeleteEventGroupAction::class)($group, $admin->externalUser);

    $admin->refresh();

    expect(EventGroup::query()->find($group->id))->toBeNull()
        ->and($admin->event_group_id)->toBeNull()
        ->and($admin->group_membership_status)->toBeNull()
        ->and($admin->group_membership_role)->toBeNull();
});

it('keeps groups with members or pending requests', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    AthleteRegistration::factory()->pendingGroup($group)->create();

    expect(fn () => resolve(DeleteEventGroupAction::class)($group, $admin->externalUser))
        ->toThrow(ValidationException::class);
});

it('blocks deletion after event end', function (): void {
    $event = eventGroupTestEvent(ended: true);
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();

    expect(fn () => resolve(DeleteEventGroupAction::class)($group, $admin->externalUser))
        ->toThrow(ValidationException::class);
});
