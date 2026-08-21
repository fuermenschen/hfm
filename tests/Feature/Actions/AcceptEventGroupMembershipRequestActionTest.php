<?php

use App\Actions\AcceptEventGroupMembershipRequestAction;
use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Notifications\EventGroupMembershipAccepted;
use App\Notifications\EventGroupMembershipDenied;
use App\Notifications\EventGroupMembershipRequested;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

it('accepts one pending request once and notifies its applicant once', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $applicant = AthleteRegistration::factory()->pendingGroup($group)->create();
    Notification::fake();
    $action = resolve(AcceptEventGroupMembershipRequestAction::class);

    $action($group, $applicant, $admin->externalUser);
    $action($group, $applicant, $admin->externalUser);

    $applicant->refresh();

    expect($applicant->group_membership_status)->toBe(GroupMembershipStatus::Accepted)
        ->and($applicant->group_membership_role)->toBe(GroupMembershipRole::Member);
    Notification::assertSentTo($applicant->externalUser, EventGroupMembershipAccepted::class);
    Notification::assertSentTimes(EventGroupMembershipAccepted::class, 1);
});

it('blocks processing by non-admins and after event end', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $applicant = AthleteRegistration::factory()->pendingGroup($group)->create();
    $endedEvent = eventGroupTestEvent(ended: true);
    $endedGroup = EventGroup::factory()->forEvent($endedEvent)->create();
    $endedAdmin = AthleteRegistration::factory()->acceptedAdmin($endedGroup)->create();
    $endedApplicant = AthleteRegistration::factory()->pendingGroup($endedGroup)->create();
    $action = resolve(AcceptEventGroupMembershipRequestAction::class);

    expect(fn () => $action($group, $applicant, $applicant->externalUser))->toThrow(AuthorizationException::class)
        ->and(fn () => $action($endedGroup, $endedApplicant, $endedAdmin->externalUser))->toThrow(ValidationException::class)
        ->and($admin->group_membership_role)->not->toBeNull();
});

it('rejects processing registrations from another event', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $otherEventApplicant = eventGroupTestRegistration(eventGroupTestEvent());

    expect(fn () => resolve(AcceptEventGroupMembershipRequestAction::class)($group, $otherEventApplicant, $admin->externalUser))
        ->toThrow(ValidationException::class);
});

it('keeps all group notifications synchronous', function (): void {
    $requested = new EventGroupMembershipRequested('Mira', 'Team Blau', 'HfM', 'Mira M.');
    $accepted = new EventGroupMembershipAccepted('Mira', 'Team Blau', 'HfM');
    $denied = new EventGroupMembershipDenied('Mira', 'Team Blau', 'HfM');

    expect($requested)->not->toBeInstanceOf(ShouldQueue::class)
        ->and($accepted)->not->toBeInstanceOf(ShouldQueue::class)
        ->and($denied)->not->toBeInstanceOf(ShouldQueue::class)
        ->and($requested->toMail(new ExternalUser)->greeting)->toBe('Hallo Mira')
        ->and($accepted->toMail(new ExternalUser)->greeting)->toBe('Hallo Mira')
        ->and($denied->toMail(new ExternalUser)->greeting)->toBe('Hallo Mira');
});
