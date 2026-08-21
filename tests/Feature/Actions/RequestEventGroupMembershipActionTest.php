<?php

use App\Actions\RequestEventGroupMembershipAction;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Notifications\EventGroupMembershipRequested;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

it('creates a pending request and notifies every accepted admin once', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $firstAdmin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $secondAdmin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $applicant = eventGroupTestRegistration($event);
    Notification::fake();
    $action = resolve(RequestEventGroupMembershipAction::class);

    $action($group, $applicant->externalUser);

    $applicant->refresh();

    expect($applicant->group_membership_status)->toBe(GroupMembershipStatus::Pending)
        ->and($applicant->group_membership_role)->toBeNull()
        ->and($applicant->eventGroup->is($group))->toBeTrue();
    Notification::assertSentTo($firstAdmin->externalUser, EventGroupMembershipRequested::class);
    Notification::assertSentTo($secondAdmin->externalUser, EventGroupMembershipRequested::class);
    Notification::assertSentTimes(EventGroupMembershipRequested::class, 2);
});

it('rejects group requests without a verified registration or after event end', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $unverified = eventGroupTestRegistration($event);
    $unverified->update(['verified' => false]);
    $endedEvent = eventGroupTestEvent(ended: true);
    $endedGroup = EventGroup::factory()->forEvent($endedEvent)->create();
    $endedRegistration = eventGroupTestRegistration($endedEvent);
    $action = resolve(RequestEventGroupMembershipAction::class);

    expect(fn () => $action($group, $unverified->externalUser))->toThrow(ValidationException::class)
        ->and(fn () => $action($endedGroup, $endedRegistration->externalUser))->toThrow(ValidationException::class);
});
