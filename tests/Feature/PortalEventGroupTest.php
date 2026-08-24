<?php

use App\Components\PortalEventGroupActions;
use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Models\SportType;
use App\Notifications\EventGroupMembershipAccepted;
use App\Notifications\EventGroupMembershipRequested;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('shows group entry points in confirmed participation cards', function (): void {
    $event = eventGroupTestEvent();
    $registration = eventGroupTestRegistration($event);

    actingAs($registration->externalUser, 'external');

    $response = get(route('portal.participations'));

    $response
        ->assertSuccessful()
        ->assertSeeText('Gruppe finden oder gründen')
        ->assertSee(route('portal.event-groups.discover', $registration), false);

    expect(substr_count($response->getContent(), 'Gruppe finden oder gründen'))->toBe(1);
});

it('keeps group controls hidden until participation is confirmed', function (): void {
    $event = eventGroupTestEvent();
    $registration = eventGroupTestRegistration($event);
    $registration->update(['verified' => false]);

    actingAs($registration->externalUser, 'external');

    get(route('portal.participations'))
        ->assertSuccessful()
        ->assertSeeText('Teilnahme bestätigen')
        ->assertDontSeeText('Gruppe finden')
        ->assertDontSeeText('Gruppe gründen');
    get(route('portal.event-groups.discover', $registration))->assertNotFound();
});

it('shows only group metadata to confirmed non-members', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create(['name' => 'Team Blau']);
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();
    $viewer = eventGroupTestRegistration($event);
    $member->externalUser->update(['email' => 'secret@example.test', 'phone_number' => '+41790000000']);

    actingAs($viewer->externalUser, 'external');

    get(route('portal.event-groups.show', $group))
        ->assertSuccessful()
        ->assertSeeText('Noch nicht Mitglied')
        ->assertSeeText('Team Blau')
        ->assertDontSeeText($member->externalUser->privacy_name)
        ->assertDontSee('secret@example.test')
        ->assertDontSee('+41790000000');
});

it('renders pending and admin views with only allowed identity fields', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create(['name' => 'Team Blau']);
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $pending = AthleteRegistration::factory()->pendingGroup($group)->create();
    $accepted = AthleteRegistration::factory()->acceptedMember($group)->create();
    $sportType = SportType::query()->create(['name' => 'Berglauf']);
    $accepted->update(['sport_type_id' => $sportType->id, 'rounds_estimated' => 42]);
    $pending->externalUser->update(['email' => 'pending-secret@example.test', 'address' => 'Secret Street 1']);
    $accepted->externalUser->update(['email' => 'member-secret@example.test', 'address' => 'Secret Street 2']);

    actingAs($pending->externalUser, 'external');
    get(route('portal.event-groups.show', $group))
        ->assertSuccessful()
        ->assertSeeText('Deine Anfrage ist offen')
        ->assertSeeText('Anfrage offen')
        ->assertDontSeeText($accepted->externalUser->privacy_name)
        ->assertDontSee('member-secret@example.test');

    actingAs($admin->externalUser, 'external');
    get(route('portal.event-groups.show', $group))
        ->assertSuccessful()
        ->assertSeeText('Administrator:in')
        ->assertSeeText('1 offene Anfragen')
        ->assertSeeText($accepted->externalUser->privacy_name)
        ->assertSeeText('Berglauf')
        ->assertSeeText('Geschätzte Runden')
        ->assertSeeText('42')
        ->assertSeeText($pending->externalUser->privacy_name)
        ->assertDontSee('pending-secret@example.test')
        ->assertDontSee('member-secret@example.test')
        ->assertDontSee('Secret Street 1')
        ->assertDontSee('Secret Street 2');

    actingAs($accepted->externalUser, 'external');

    get(route('portal.event-groups.show', $group))
        ->assertSuccessful()
        ->assertSeeText('Mitglied');
});

it('allows confirmed athletes to request a group and redirects to its detail page', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $registration = eventGroupTestRegistration($event);

    Livewire::actingAs($registration->externalUser, 'external')
        ->test(PortalEventGroupActions::class, [
            'registrationId' => $registration->id,
            'action' => 'request',
            'groupId' => $group->id,
        ])
        ->call('submit')
        ->assertRedirect(route('portal.event-groups.show', $group));

    expect($registration->refresh()->event_group_id)->toBe($group->id)
        ->and($registration->group_membership_status?->value)->toBe('pending');
});

it('rejects direct group access for athletes from another event', function (): void {
    $group = EventGroup::factory()->forEvent(eventGroupTestEvent())->create();
    $other = eventGroupTestRegistration(eventGroupTestEvent());

    actingAs($other->externalUser, 'external');

    get(route('portal.event-groups.show', $group))->assertForbidden();
});

it('limits discovery to the owned participation event and accepted counts', function (): void {
    $event = eventGroupTestEvent();
    $otherEvent = eventGroupTestEvent();
    $registration = eventGroupTestRegistration($event);
    $visible = EventGroup::factory()->forEvent($event)->create(['name' => 'Sichtbar']);
    AthleteRegistration::factory()->acceptedAdmin($visible)->create();
    AthleteRegistration::factory()->pendingGroup($visible)->create();
    $hidden = EventGroup::factory()->forEvent($otherEvent)->create(['name' => 'Versteckt']);

    actingAs($registration->externalUser, 'external');

    get(route('portal.event-groups.discover', $registration))
        ->assertSuccessful()
        ->assertSeeText('Sichtbar')
        ->assertSeeText('1 bestätigte Mitglieder')
        ->assertDontSeeText($hidden->name);
});

it('explains that ended empty discovery is read-only', function (): void {
    $event = eventGroupTestEvent(ended: true);
    $registration = eventGroupTestRegistration($event);

    actingAs($registration->externalUser, 'external');

    get(route('portal.event-groups.discover', $registration))
        ->assertSuccessful()
        ->assertSeeText('Für diesen Anlass sind keine Gruppen archiviert.')
        ->assertDontSeeText('Gründe die erste Gruppe für diesen Anlass.');
});

it('creates, withdraws, and deletes groups through portal actions', function (): void {
    $event = eventGroupTestEvent();
    $creator = eventGroupTestRegistration($event);

    Livewire::actingAs($creator->externalUser, 'external')
        ->test(PortalEventGroupActions::class, ['registrationId' => $creator->id, 'action' => 'create'])
        ->set('name', 'Bergfüchse')
        ->call('submit')
        ->assertRedirect(route('portal.event-groups.show', EventGroup::query()->firstOrFail()));

    $group = EventGroup::query()->firstOrFail();
    expect($creator->refresh()->group_membership_status)->toBe(GroupMembershipStatus::Accepted)
        ->and($creator->group_membership_role)->toBe(GroupMembershipRole::Admin);

    $applicant = eventGroupTestRegistration($event);
    Livewire::actingAs($applicant->externalUser, 'external')
        ->test(PortalEventGroupActions::class, ['registrationId' => $applicant->id, 'action' => 'request', 'groupId' => $group->id])
        ->call('submit');
    Livewire::actingAs($applicant->externalUser, 'external')
        ->test(PortalEventGroupActions::class, ['registrationId' => $applicant->id, 'action' => 'withdraw', 'groupId' => $group->id])
        ->call('submit')
        ->assertRedirect(route('portal.event-groups.show', $group));

    expect($applicant->refresh()->event_group_id)->toBeNull();

    Livewire::actingAs($creator->externalUser, 'external')
        ->test(PortalEventGroupActions::class, ['registrationId' => $creator->id, 'action' => 'delete', 'groupId' => $group->id])
        ->call('submit')
        ->assertRedirect(route('portal.participations', ['anlass' => $event->slug]));

    expect(EventGroup::query()->find($group->id))->toBeNull();
});

it('shows create loading feedback', function (): void {
    $event = eventGroupTestEvent();
    $registration = eventGroupTestRegistration($event);

    Livewire::actingAs($registration->externalUser, 'external')
        ->test(PortalEventGroupActions::class, ['registrationId' => $registration->id, 'action' => 'create'])
        ->assertSee('Gruppe verbindlich gründen')
        ->assertSee('Wird gespeichert ...');
});

it('processes pending applicants and member roles through portal actions', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $applicant = AthleteRegistration::factory()->pendingGroup($group)->create();

    Livewire::actingAs($admin->externalUser, 'external')
        ->test(PortalEventGroupActions::class, ['registrationId' => $admin->id, 'action' => 'accept', 'groupId' => $group->id, 'targetRegistrationId' => $applicant->id])
        ->call('submit');
    expect($applicant->refresh()->group_membership_role)->toBe(GroupMembershipRole::Member);

    Livewire::actingAs($admin->externalUser, 'external')
        ->test(PortalEventGroupActions::class, ['registrationId' => $admin->id, 'action' => 'promote', 'groupId' => $group->id, 'targetRegistrationId' => $applicant->id])
        ->call('submit');
    expect($applicant->refresh()->group_membership_role)->toBe(GroupMembershipRole::Admin);

    Livewire::actingAs($admin->externalUser, 'external')
        ->test(PortalEventGroupActions::class, ['registrationId' => $admin->id, 'action' => 'demote', 'groupId' => $group->id, 'targetRegistrationId' => $applicant->id])
        ->call('submit');
    expect($applicant->refresh()->group_membership_role)->toBe(GroupMembershipRole::Member);

    Livewire::actingAs($admin->externalUser, 'external')
        ->test(PortalEventGroupActions::class, ['registrationId' => $admin->id, 'action' => 'remove', 'groupId' => $group->id, 'targetRegistrationId' => $applicant->id])
        ->call('submit');
    expect($applicant->refresh()->event_group_id)->toBeNull();
});

it('allows members to leave and admins to deny requests through portal actions', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();
    $applicant = AthleteRegistration::factory()->pendingGroup($group)->create();

    Livewire::actingAs($member->externalUser, 'external')
        ->test(PortalEventGroupActions::class, ['registrationId' => $member->id, 'action' => 'leave', 'groupId' => $group->id])
        ->call('submit');
    expect($member->refresh()->event_group_id)->toBeNull();

    Livewire::actingAs($admin->externalUser, 'external')
        ->test(PortalEventGroupActions::class, ['registrationId' => $admin->id, 'action' => 'deny', 'groupId' => $group->id, 'targetRegistrationId' => $applicant->id])
        ->call('submit');
    expect($applicant->refresh()->event_group_id)->toBeNull();
});

it('renders accepted archive members only and no mutations after event end', function (): void {
    $event = eventGroupTestEvent(ended: true);
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();
    $pending = AthleteRegistration::factory()->pendingGroup($group)->create();

    actingAs($admin->externalUser, 'external');

    get(route('portal.event-groups.show', $group))
        ->assertSuccessful()
        ->assertSeeText('Archiv')
        ->assertSeeText($member->externalUser->privacy_name)
        ->assertDontSeeText($pending->externalUser->privacy_name)
        ->assertDontSeeText('Offene Anfragen')
        ->assertDontSeeText('Gruppe verlassen');

    actingAs($pending->externalUser, 'external');

    get(route('portal.event-groups.show', $group))
        ->assertSuccessful()
        ->assertSeeText('Noch nicht Mitglied')
        ->assertDontSeeText('Anfrage offen');

    actingAs($admin->externalUser, 'external');

    get(route('portal.participations', ['anlass' => $event->slug]))
        ->assertSuccessful()
        ->assertSeeText($group->name)
        ->assertSeeText('2 bestätigte Mitglieder')
        ->assertSeeText('Archiv öffnen')
        ->assertSee(route('portal.event-groups.show', $group), false);
});

it('sends signed group links in request and outcome emails', function (): void {
    Notification::fake();
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create();
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $applicant = eventGroupTestRegistration($event);

    Livewire::actingAs($applicant->externalUser, 'external')
        ->test(PortalEventGroupActions::class, ['registrationId' => $applicant->id, 'action' => 'request', 'groupId' => $group->id])
        ->call('submit');

    Notification::assertSentTo($admin->externalUser, EventGroupMembershipRequested::class, function (EventGroupMembershipRequested $notification) use ($admin, $group): bool {
        $url = $notification->toMail($admin->externalUser)->actionUrl;
        get($url)->assertRedirect(route('portal.event-groups.show', $group));

        return str_contains($url, 'redirect=group%3A'.$group->id);
    });

    Livewire::actingAs($admin->externalUser, 'external')
        ->test(PortalEventGroupActions::class, ['registrationId' => $admin->id, 'action' => 'accept', 'groupId' => $group->id, 'targetRegistrationId' => $applicant->id])
        ->call('submit');

    Notification::assertSentTo($applicant->externalUser, EventGroupMembershipAccepted::class, fn (EventGroupMembershipAccepted $notification): bool => $notification->toMail($applicant->externalUser)->actionUrl !== null);

});
