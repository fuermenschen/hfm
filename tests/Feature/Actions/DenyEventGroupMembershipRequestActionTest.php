<?php

use App\Actions\DenyEventGroupMembershipRequestAction;
use App\Actions\RequestEventGroupMembershipAction;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Notifications\EventGroupMembershipDenied;
use Illuminate\Support\Facades\Notification;

it('denies one pending request, clears it, and notifies its applicant once', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $applicant = AthleteRegistration::factory()->pendingGroup($group)->create();
    Notification::fake();
    $action = resolve(DenyEventGroupMembershipRequestAction::class);

    $action($group, $applicant, $admin->externalUser);
    $action($group, $applicant, $admin->externalUser);

    $applicant->refresh();

    expect($applicant->event_group_id)->toBeNull()
        ->and($applicant->group_membership_status)->toBeNull()
        ->and($applicant->group_membership_role)->toBeNull();
    Notification::assertSentTo($applicant->externalUser, EventGroupMembershipDenied::class);
    Notification::assertSentTimes(EventGroupMembershipDenied::class, 1);
});

it('lets a denied applicant request the same group again', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $applicant = AthleteRegistration::factory()->pendingGroup($group)->create();

    resolve(DenyEventGroupMembershipRequestAction::class)($group, $applicant, $admin->externalUser);
    resolve(RequestEventGroupMembershipAction::class)($group, $applicant->externalUser);

    expect($applicant->refresh()->event_group_id)->toBe($group->id);
});
