<?php

use App\Actions\LeaveEventGroupAction;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Notifications\EventGroupMemberLeft;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

it('allows members and non-last admins to leave', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $firstAdmin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $secondAdmin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();
    Notification::fake();
    $action = resolve(LeaveEventGroupAction::class);

    $action($group, $firstAdmin->externalUser);
    $action($group, $member->externalUser);

    $member->refresh();
    $firstAdmin->refresh();

    expect($member->event_group_id)->toBeNull()
        ->and($member->group_membership_status)->toBeNull()
        ->and($member->group_membership_role)->toBeNull()
        ->and($firstAdmin->event_group_id)->toBeNull()
        ->and($firstAdmin->group_membership_status)->toBeNull()
        ->and($firstAdmin->group_membership_role)->toBeNull()
        ->and($secondAdmin->refresh()->event_group_id)->toBe($group->id);

    Notification::assertSentTo($secondAdmin->externalUser, EventGroupMemberLeft::class, function (EventGroupMemberLeft $notification) use ($member): bool {
        return $notification->memberName === $member->externalUser->privacy_name;
    });
    Notification::assertSentTo($secondAdmin->externalUser, EventGroupMemberLeft::class, function (EventGroupMemberLeft $notification) use ($firstAdmin): bool {
        return $notification->memberName === $firstAdmin->externalUser->privacy_name;
    });
    Notification::assertNotSentTo($firstAdmin->externalUser, EventGroupMemberLeft::class);
    Notification::assertNotSentTo($member->externalUser, EventGroupMemberLeft::class);

    Notification::assertSentTo($secondAdmin->externalUser, EventGroupMemberLeft::class, function (EventGroupMemberLeft $notification) use ($secondAdmin, $group): bool {
        $mail = $notification->toMail($secondAdmin->externalUser);

        return $mail->actionText === 'Gruppe öffnen'
            && str_contains($mail->actionUrl, 'redirect=group%3A'.$group->id);
    });
});

it('does not let the last admin leave or anyone mutate after event end', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $endedEvent = eventGroupTestEvent(ended: true);
    $endedGroup = EventGroup::factory()->forEvent($endedEvent)->create();
    $endedMember = AthleteRegistration::factory()->acceptedMember($endedGroup)->create();
    $action = resolve(LeaveEventGroupAction::class);

    expect(fn () => $action($group, $admin->externalUser))->toThrow(ValidationException::class)
        ->and(fn () => $action($endedGroup, $endedMember->externalUser))->toThrow(ValidationException::class);
});
