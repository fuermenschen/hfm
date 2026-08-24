<?php

use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use Illuminate\Support\Facades\URL;

it('lets an athlete create a group from their portal participation', function (): void {
    $event = eventGroupTestEvent();
    $creator = eventGroupTestRegistration($event);

    $page = visit(portalLoginUrl($creator->externalUser))
        ->assertPathIs('/portal')
        ->click('Teilnahmen')
        ->assertPathIs('/portal/teilnahmen')
        ->click('Gruppe gründen')
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

it('shows member and admin management controls without leaking private profile fields', function (): void {
    $event = eventGroupTestEvent();
    $group = EventGroup::factory()->forEvent($event)->create(['name' => 'Waldläufer']);
    $admin = AthleteRegistration::factory()->acceptedAdmin($group)->create();
    $member = AthleteRegistration::factory()->acceptedMember($group)->create();
    $member->externalUser->update(['email' => 'private@example.test', 'phone_number' => '+41790000000']);

    visit(portalLoginUrl($admin->externalUser))
        ->navigate(route('portal.event-groups.show', $group))
        ->assertSee('Zu Administrator:in machen')
        ->assertSee('Entfernen')
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

    visit(portalLoginUrl($admin->externalUser))
        ->navigate(route('portal.event-groups.show', $group))
        ->assertPresent('internal:role=button[name="Zu Administrator:in machen"]')
        ->click('internal:role=button[name="Zu Administrator:in machen"]')
        ->assertPresent('dialog[open] >> internal:role=button[name="Zu Administrator:in machen"]')
        ->click('dialog[open] >> internal:role=button[name="Zu Administrator:in machen"]')
        ->assertSee('Das Mitglied ist jetzt Administrator:in.')
        ->assertSee('Administratorrechte entfernen')
        ->assertPresent('internal:role=button[name="Entfernen"]')
        ->click('internal:role=button[name="Entfernen"]')
        ->assertPresent('dialog[open] >> internal:role=button[name="Entfernen"]')
        ->click('dialog[open] >> internal:role=button[name="Entfernen"]')
        ->assertSee('Das Mitglied wurde entfernt.')
        ->assertNoJavaScriptErrors();

    expect($member->refresh()->event_group_id)->toBeNull();

    $leaver = AthleteRegistration::factory()->acceptedMember($group)->create();

    visit(portalLoginUrl($leaver->externalUser))
        ->navigate(route('portal.event-groups.show', $group))
        ->assertPresent('internal:role=button[name="Gruppe verlassen"]')
        ->click('internal:role=button[name="Gruppe verlassen"]')
        ->assertPresent('dialog[open] >> internal:role=button[name="Gruppe verlassen"]')
        ->click('dialog[open] >> internal:role=button[name="Gruppe verlassen"]')
        ->assertPathIs('/portal/gruppen/'.$group->id)
        ->assertSee('Du hast die Gruppe verlassen.')
        ->assertNoJavaScriptErrors();

    expect($leaver->refresh()->event_group_id)->toBeNull();
});

function portalLoginUrl(ExternalUser $externalUser): string
{
    return URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), ['uuid' => $externalUser->uuid]);
}
