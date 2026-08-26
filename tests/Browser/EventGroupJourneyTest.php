<?php

use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Models\SportType;
use App\Notifications\EventGroupMemberLeft;
use App\Notifications\EventGroupMemberRemoved;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('lets an athlete create a group from their portal participation', function (): void {
    $event = eventGroupTestEvent();
    $creator = eventGroupTestRegistration($event);

    $page = visit(portalLoginUrl($creator->externalUser))
        ->assertPathIs('/portal')
        ->click('Teilnahmen')
        ->assertPathIs('/portal/teilnahmen')
        ->click('internal:role=button[name="Gruppe"]')
        ->click('Gruppe finden oder gründen')
        ->assertSee('Eigene Gruppe gründen')
        ->type('[wire\:model="name"]', 'Bergfüchse')
        ->press('Gruppe verbindlich gründen')
        ->assertSee('Bergfüchse')
        ->assertSee('Administrator:in')
        ->wait(0.2)
        ->assertNoJavaScriptErrors();

    $group = EventGroup::query()->where('name', 'Bergfüchse')->firstOrFail();
    expect($creator->refresh()->event_group_id)->toBe($group->id);
});

it('lets an applicant request membership and an admin accept it', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create(['name' => 'Gipfelteam']);
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $applicant = eventGroupTestRegistration($event);

    $page = visit(portalLoginUrl($applicant->externalUser))
        ->navigate(route('portal.event-groups.discover', $applicant))
        ->assertSee('Gipfelteam')
        ->assertSee('Beitritt anfragen')
        ->click('Beitritt anfragen')
        ->assertSee('Beitritt anfragen?')
        ->assertPresent('dialog[open] >> internal:role=button[name="Beitritt anfragen"]')
        ->click('dialog[open] >> internal:role=button[name="Beitritt anfragen"]')
        ->assertSee('Deine Anfrage ist offen')
        ->assertNoJavaScriptErrors();

    expect($applicant->refresh()->event_group_id)->toBe($group->id);

    $page->navigate(portalLoginUrl($admin->externalUser))
        ->navigate(route('portal.event-groups.show', $group))
        ->assertSee('Offene Anfragen (1)')
        ->assertSee($applicant->externalUser->privacy_name)
        ->click('Annehmen')
        ->assertSee('Annehmen?')
        ->assertPresent('dialog[open] >> internal:role=button[name="Annehmen"]')
        ->click('dialog[open] >> internal:role=button[name="Annehmen"]')
        ->assertSee('Die Anfrage wurde angenommen.')
        ->assertSee($applicant->externalUser->privacy_name)
        ->assertNoJavaScriptErrors();

    expect($applicant->refresh()->group_membership_role?->value)->toBe('member');
});

it('lets an applicant withdraw a pending request and request again', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create(['name' => 'Gipfelteam']);
    AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $applicant = eventGroupTestRegistration($event);

    $page = visit(portalLoginUrl($applicant->externalUser))
        ->navigate(route('portal.event-groups.discover', $applicant))
        ->click('Beitritt anfragen')
        ->assertPresent('dialog[open] >> internal:role=button[name="Beitritt anfragen"]')
        ->click('dialog[open] >> internal:role=button[name="Beitritt anfragen"]')
        ->assertSee('Deine Anfrage ist offen')
        ->click('Anfrage zurückziehen')
        ->assertSee('Anfrage zurückziehen?')
        ->assertPresent('dialog[open] >> internal:role=button[name="Anfrage zurückziehen"]')
        ->click('dialog[open] >> internal:role=button[name="Anfrage zurückziehen"]')
        ->assertSee('Deine Beitrittsanfrage wurde zurückgezogen.')
        ->navigate(route('portal.event-groups.discover', $applicant))
        ->assertSee('Beitritt anfragen');

    expect($applicant->refresh()->event_group_id)->toBeNull();
    $page->assertNoJavaScriptErrors();
});

it('lets a denied applicant request the same group again', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create(['name' => 'Gipfelteam']);
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $applicant = eventGroupTestRegistration($event);

    $page = visit(portalLoginUrl($applicant->externalUser))
        ->navigate(route('portal.event-groups.discover', $applicant))
        ->click('Beitritt anfragen')
        ->assertPresent('dialog[open] >> internal:role=button[name="Beitritt anfragen"]')
        ->click('dialog[open] >> internal:role=button[name="Beitritt anfragen"]')
        ->assertSee('Deine Anfrage ist offen');

    $page->navigate(portalLoginUrl($admin->externalUser))
        ->navigate(route('portal.event-groups.show', $group))
        ->click('Ablehnen')
        ->assertSee('Ablehnen?')
        ->assertPresent('dialog[open] >> internal:role=button[name="Ablehnen"]')
        ->click('dialog[open] >> internal:role=button[name="Ablehnen"]')
        ->assertSee('Die Anfrage wurde abgelehnt.')
        ->assertNoJavaScriptErrors();

    $page->navigate(portalLoginUrl($applicant->externalUser))
        ->navigate(route('portal.event-groups.discover', $applicant))
        ->assertSee('Beitritt anfragen')
        ->click('Beitritt anfragen')
        ->assertPresent('dialog[open] >> internal:role=button[name="Beitritt anfragen"]')
        ->click('dialog[open] >> internal:role=button[name="Beitritt anfragen"]')
        ->assertSee('Deine Anfrage ist offen')
        ->assertNoJavaScriptErrors();

    expect($applicant->refresh()->event_group_id)->toBe($group->id);
});

it('opens an ended group archive from participations', function (): void {
    $event = eventGroupTestEvent(ended: true);
    $group = EventGroup::factory()->forEvent($event)->create(['name' => 'Archivteam']);
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();
    $pending = AthleteRegistration::factory()->pendingGroup($group)->create();

    $page = visit(portalLoginUrl($admin->externalUser))
        ->click('Teilnahmen')
        ->click('internal:role=button[name="Gruppe"]')
        ->assertSee('Archivteam')
        ->assertSee('2 bestätigte Mitglieder')
        ->click('Archiv öffnen')
        ->assertPathIs('/portal/gruppen/'.$group->id)
        ->assertSee('Archiv')
        ->assertSee($member->externalUser->privacy_name)
        ->assertDontSee($pending->externalUser->privacy_name)
        ->assertDontSee('Offene Anfragen')
        ->assertDontSee('Gruppe verlassen')
        ->assertNoJavaScriptErrors();

    expect($page->script('document.querySelector("h1")?.textContent'))->toContain('Archivteam');
});

it('lets a sole admin delete a group through the confirmation dialog', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create(['name' => 'Löschteam']);
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();

    visit(portalLoginUrl($admin->externalUser))
        ->navigate(route('portal.event-groups.show', $group))
        ->assertSee('Gruppe löschen')
        ->click('Gruppe löschen')
        ->assertSee('Gruppe löschen?')
        ->assertSee('Löschteam')
        ->assertPresent('dialog[open] >> internal:role=button[name="Gruppe löschen"]')
        ->click('dialog[open] >> internal:role=button[name="Gruppe löschen"]')
        ->assertPathIs('/portal/teilnahmen')
        ->assertDontSee('Löschteam')
        ->assertNoJavaScriptErrors();

    expect(EventGroup::query()->find($group->id))->toBeNull()
        ->and($admin->refresh()->event_group_id)->toBeNull();
});

it('renders group management clearly on mobile', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create(['name' => 'Mobileteam']);
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    AthleteRegistration::factory()->pendingGroup($group)->create();

    visit(portalLoginUrl($admin->externalUser))
        ->on()->mobile()
        ->navigate(route('portal.event-groups.show', $group))
        ->assertSee('Mobileteam')
        ->assertSee('Administrator:in')
        ->assertSee('1 offene Anfragen')
        ->assertSee('Offene Anfragen (1)')
        ->assertSee('Annehmen')
        ->assertNoJavaScriptErrors();
});

it('shows member and admin management controls without leaking private profile fields', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create(['name' => 'Waldläufer']);
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();
    $sportType = SportType::query()->create(['name' => 'Berglauf']);
    $member->update(['sport_type_id' => $sportType->id, 'rounds_estimated' => 42]);
    $member->externalUser->update(['email' => 'private@example.test', 'phone_number' => '+41790000000']);

    visit(portalLoginUrl($admin->externalUser))
        ->navigate(route('portal.event-groups.show', $group))
        ->assertSee('Zu Administrator:in machen')
        ->assertSee('Aus Gruppe entfernen')
        ->assertSee('Berglauf')
        ->assertSee('Geschätzte Runden')
        ->assertSee('42')
        ->assertDontSee('private@example.test')
        ->assertDontSee('+41790000000')
        ->assertNoJavaScriptErrors();

    visit(portalLoginUrl($member->externalUser))
        ->navigate(route('portal.event-groups.show', $group))
        ->assertSee('Gruppe verlassen')
        ->assertDontSee('Offene Anfragen')
        ->assertNoJavaScriptErrors();
});

it('lets an admin promote and remove a member and lets another member leave', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create(['name' => 'Alpenteam']);
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();
    Notification::fake();

    visit(portalLoginUrl($admin->externalUser))
        ->navigate(route('portal.event-groups.show', $group))
        ->assertPresent('internal:role=button[name="Zu Administrator:in machen"]')
        ->click('internal:role=button[name="Zu Administrator:in machen"]')
        ->assertSee('Möchtest du '.$member->externalUser->privacy_name.' zum/zur Administrator:in der Gruppe "Alpenteam" machen?')
        ->assertPresent('dialog[open] >> internal:role=button[name="Zu Administrator:in machen"]')
        ->click('dialog[open] >> internal:role=button[name="Zu Administrator:in machen"]')
        ->assertSee('Das Mitglied ist jetzt Administrator:in.')
        ->assertSee('Admin-Rechte entfernen')
        ->click('Admin-Rechte entfernen')
        ->assertSee('Möchtest du die Admin-Rechte von '.$member->externalUser->privacy_name.' entfernen?')
        ->assertPresent('dialog[open] >> internal:role=button[name="Admin-Rechte entfernen"]')
        ->click('dialog[open] >> internal:role=button[name="Admin-Rechte entfernen"]')
        ->assertSee('Administratorrechte wurden entfernt.')
        ->assertPresent('internal:role=button[name="Aus Gruppe entfernen"]')
        ->click('internal:role=button[name="Aus Gruppe entfernen"]')
        ->assertSee('Möchtest du '.$member->externalUser->privacy_name.' aus der Gruppe "Alpenteam" entfernen?')
        ->assertPresent('dialog[open] >> internal:role=button[name="Aus Gruppe entfernen"]')
        ->click('dialog[open] >> internal:role=button[name="Aus Gruppe entfernen"]')
        ->assertSee('Das Mitglied wurde entfernt.')
        ->assertNoJavaScriptErrors();

    expect($member->refresh()->event_group_id)->toBeNull();
    Notification::assertSentTo($member->externalUser, EventGroupMemberRemoved::class, function (EventGroupMemberRemoved $notification) use ($event, $group, $member): bool {
        $mail = $notification->toMail($member->externalUser);

        return $notification->groupName === $group->name
            && $notification->eventTitle === $event->title
            && $mail->actionText === 'Zum Portal';
    });

    $leaver = AthleteRegistration::factory()->acceptedMember($group)->create();

    visit(portalLoginUrl($leaver->externalUser))
        ->navigate(route('portal.event-groups.show', $group))
        ->assertPresent('internal:role=button[name="Gruppe verlassen"]')
        ->click('internal:role=button[name="Gruppe verlassen"]')
        ->assertSee('Möchtest du die Gruppe "Alpenteam" verlassen?')
        ->assertPresent('dialog[open] >> internal:role=button[name="Gruppe verlassen"]')
        ->click('dialog[open] >> internal:role=button[name="Gruppe verlassen"]')
        ->assertPathIs('/portal/gruppen/'.$group->id)
        ->assertSee('Du hast die Gruppe verlassen.')
        ->assertNoJavaScriptErrors();

    expect($leaver->refresh()->event_group_id)->toBeNull();
    Notification::assertSentTo($admin->externalUser, EventGroupMemberLeft::class, function (EventGroupMemberLeft $notification) use ($admin, $group, $leaver): bool {
        $mail = $notification->toMail($admin->externalUser);

        return $notification->memberName === $leaver->externalUser->privacy_name
            && $notification->groupName === $group->name
            && $mail->actionText === 'Gruppe öffnen';
    });
});

function portalLoginUrl(ExternalUser $externalUser): string
{
    return URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), ['uuid' => $externalUser->uuid]);
}
