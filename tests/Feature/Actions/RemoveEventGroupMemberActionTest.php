<?php

use App\Actions\RemoveEventGroupMemberAction;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Notifications\EventGroupMemberRemoved;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

it('lets an admin remove accepted members', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();
    Notification::fake();

    resolve(RemoveEventGroupMemberAction::class)($group, $member, $admin->externalUser);

    $member->refresh();

    expect($member->event_group_id)->toBeNull()
        ->and($member->group_membership_status)->toBeNull()
        ->and($member->group_membership_role)->toBeNull();

    Notification::assertSentTo($member->externalUser, EventGroupMemberRemoved::class, function (EventGroupMemberRemoved $notification) use ($event, $group, $member): bool {
        $mail = $notification->toMail($member->externalUser);

        return $notification->groupName === $group->name
            && $notification->eventTitle === $event->title
            && $mail->actionText === 'Zum Portal'
            && ! str_contains($mail->actionUrl, 'redirect=group%3A');
    });
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

it('rejects an admin registration from another event', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();
    $admin->update(['donation_event_id' => eventGroupTestEvent()->id]);

    expect(fn () => resolve(RemoveEventGroupMemberAction::class)($group, $member, $admin->externalUser))
        ->toThrow(AuthorizationException::class);
});

it('blocks removals after event end', function (): void {
    $event = eventGroupTestEvent(ended: true);
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();

    expect(fn () => resolve(RemoveEventGroupMemberAction::class)($group, $member, $admin->externalUser))
        ->toThrow(ValidationException::class);
});
