<?php

use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use App\Settings\EventSettings;
use Carbon\Carbon;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('defaults home to current event and shows owned summary with global confirmation callouts', function (): void {
    $currentEvent = DonationEvent::factory()->year(2036)->create(['title' => 'Current Event']);
    $previousEvent = DonationEvent::factory()->year(2035)->create(['title' => 'Previous Event']);
    $unrelatedEvent = DonationEvent::factory()->year(2034)->create(['title' => 'Unrelated Event']);
    setPortalCurrentEvent($currentEvent);

    $externalUser = ExternalUser::factory()->create(['first_name' => 'Alex']);
    $sportType = SportType::query()->create(['name' => 'Velofahren']);
    $partner = Partner::factory()->create(['name' => 'Stiftung Test']);
    $currentRegistration = AthleteRegistration::factory()->forVerifiedEventUser($currentEvent, $externalUser)->create([
        'rounds_estimated' => 10,
        'rounds_done' => 4,
    ]);
    $eventGroup = EventGroup::factory()->forEvent($currentEvent)->create(['name' => 'Team Aktuell']);
    $currentRegistration->update([
        'event_group_id' => $eventGroup->id,
        'group_membership_status' => GroupMembershipStatus::Accepted,
        'group_membership_role' => GroupMembershipRole::Admin,
    ]);
    AthleteRegistration::factory()->forEvent($previousEvent)->forExternalUser($externalUser)->create([
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
        'rounds_estimated' => 12,
        'verified' => false,
    ]);

    Donation::factory()->forPair(ExternalUser::factory()->create(), $currentRegistration)->create([
        'amount_per_round' => 5,
        'amount_min' => null,
        'amount_max' => null,
        'verified' => true,
    ]);
    Donation::factory()->forPair(ExternalUser::factory()->create(), $currentRegistration)->create(['verified' => false]);

    $otherCurrentRegistration = AthleteRegistration::factory()->forEvent($currentEvent)->verified()->create([
        'rounds_estimated' => 10,
        'rounds_done' => 2,
    ]);
    Donation::factory()->forPair($externalUser, $otherCurrentRegistration)->create([
        'amount_per_round' => 3,
        'amount_min' => null,
        'amount_max' => null,
        'verified' => true,
    ]);
    $pendingDonationRegistration = AthleteRegistration::factory()->forEvent($currentEvent)->verified()->create();
    Donation::factory()->forPair($externalUser, $pendingDonationRegistration)->create(['verified' => false]);
    $previousDonationRegistration = AthleteRegistration::factory()->forEvent($previousEvent)->verified()->create([
        'rounds_estimated' => 10,
    ]);
    $previousDonation = Donation::factory()->forDonorExternalUser($externalUser)->create([
        'athlete_registration_id' => $previousDonationRegistration->id,
        'amount_per_round' => 4,
        'amount_max' => 30,
        'verified' => false,
    ]);

    AthleteRegistration::factory()->forEvent($unrelatedEvent)->verified()->create();

    actingAs($externalUser, 'external');

    get(route('portal.dashboard'))
        ->assertSuccessful()
        ->assertViewHas('selectedEventSlug', $currentEvent->slug)
        ->assertViewHas('receivedDonationCount', 1)
        ->assertViewHas('pendingReceivedDonationCount', 1)
        ->assertViewHas('estimatedReceivedAmount', 50.0)
        ->assertViewHas('currentReceivedAmount', 20.0)
        ->assertViewHas('ownDonationCount', 1)
        ->assertViewHas('estimatedOwnAmount', 30.0)
        ->assertViewHas('currentOwnAmount', 6.0)
        ->assertSeeText('Was du mit deinen Runden für deine Begünstigte sammelst.')
        ->assertSeeText('Deine Unterstützung')
        ->assertSeeText('Deine Gruppe')
        ->assertSeeText('Team Aktuell')
        ->assertSeeText('Bestätigte Spenden')
        ->assertSeeText('Mit geschätzten Runden der Gruppe')
        ->assertDontSeeText('Anzahl bestätigter Spenden')
        ->assertDontSeeText('Geldsumme bestätigter Spenden')
        ->assertSee(route('portal.event-groups.show', $eventGroup), false)
        ->assertSeeText('Spenden (geschätzt)')
        ->assertDontSeeText('Spenden (tatsächlich)')
        ->assertSeeText('Offene Bestätigungen aus allen Anlässen.')
        ->assertSeeText('Anmeldung bestätigen')
        ->assertSeeText('Previous Event')
        ->assertSeeText('Velofahren · 12 geschätzte Runden · Stiftung Test')
        ->assertSee('wire:click="confirm"', false)
        ->assertSeeText('Erwartet: Fr. 30.00 · Maximal Fr. 30.00')
        ->assertSee('onchange="Livewire.navigate(', false)
        ->assertSee('class="w-full min-w-0"', false)
        ->assertDontSee('sm:w-80', false)
        ->assertSee('overflow-x-hidden', false)
        ->assertSeeText('Current Event · 2036')
        ->assertSeeText('Anlass wechseln')
        ->assertDontSeeText('Anzeigen')
        ->assertSeeText('Current Event')
        ->assertDontSeeText('Unrelated Event');

    get(route('portal.dashboard', ['anlass' => '']))
        ->assertSuccessful()
        ->assertViewHas('selectedEventSlug', null)
        ->assertSeeText('Spenden (geschätzt)')
        ->assertSeeText('Spenden (tatsächlich)');
});

it('shows estimated donation amount until selected event starts', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    setPortalCurrentEvent($event);

    $externalUser = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $externalUser)->create([
        'rounds_done' => 0,
    ]);
    Donation::factory()->forPair(ExternalUser::factory()->create(), $registration)->create(['verified' => true]);

    actingAs($externalUser, 'external');

    get(route('portal.dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Spenden (geschätzt)')
        ->assertDontSeeText('Spenden (tatsächlich)');
});

it('shows only confirmed pledges in participation amount summary', function (): void {
    $event = DonationEvent::factory()->year(2036)->create(['title' => 'Participation Event']);
    setPortalCurrentEvent($event);

    $externalUser = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $externalUser)->create([
        'rounds_estimated' => 10,
        'rounds_done' => 2,
    ]);
    Donation::factory()->forPair(ExternalUser::factory()->create(), $registration)->create([
        'amount_per_round' => 5,
        'amount_min' => null,
        'amount_max' => null,
        'verified' => true,
    ]);
    Donation::factory()->forPair(ExternalUser::factory()->create(), $registration)->create([
        'amount_per_round' => 7,
        'amount_min' => null,
        'amount_max' => null,
        'verified' => false,
    ]);

    actingAs($externalUser, 'external');

    get(route('portal.participations'))
        ->assertSuccessful()
        ->assertSeeText('Spenden (geschätzt, nur bestätigt)')
        ->assertSeeText('50.00')
        ->assertSeeText('Spender:innen (2)')
        ->assertDontSeeText('120.00')
        ->assertDontSee('bg-hfm-light/15', false)
        ->assertDontSee('bg-emerald-50', false)
        ->assertDontSeeText('Participation Event · '.$event->starts_at->format('d.m.Y'));
});

it('shows actual donation amount when selected event starts', function (): void {
    Carbon::setTestNow('2036-09-12 11:00:00 Europe/Zurich');
    $event = DonationEvent::factory()->year(2036)->create();
    setPortalCurrentEvent($event);

    $externalUser = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $externalUser)->create([
        'rounds_estimated' => 10,
        'rounds_done' => 0,
    ]);
    $eventGroup = EventGroup::factory()->forEvent($event)->create();
    $registration->update([
        'event_group_id' => $eventGroup->id,
        'group_membership_status' => GroupMembershipStatus::Accepted,
        'group_membership_role' => GroupMembershipRole::Admin,
    ]);
    Donation::factory()->forPair(ExternalUser::factory()->create(), $registration)->create([
        'amount_per_round' => 5,
        'amount_min' => null,
        'amount_max' => null,
        'verified' => true,
    ]);
    $otherRegistration = AthleteRegistration::factory()->forEvent($event)->verified()->create([
        'rounds_estimated' => 10,
        'rounds_done' => 0,
    ]);
    Donation::factory()->forPair($externalUser, $otherRegistration)->create([
        'amount_per_round' => 5,
        'amount_min' => null,
        'amount_max' => null,
        'verified' => true,
    ]);

    actingAs($externalUser, 'external');

    get(route('portal.dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Spenden (tatsächlich)')
        ->assertSeeText('Mit absolvierten Runden der Gruppe')
        ->assertSeeText('Fr. 0.00')
        ->assertDontSeeText('Geldsumme bestätigter Spenden')
        ->assertDontSeeText('Spenden (geschätzt)');

    get(route('portal.donations'))
        ->assertSuccessful()
        ->assertSeeText('Spenden (tatsächlich)')
        ->assertDontSeeText('Spenden (geschätzt)')
        ->assertDontSeeText('Noch nicht final');

    get(route('portal.participations'))
        ->assertSuccessful()
        ->assertSeeText('Tatsächlicher Betrag')
        ->assertDontSeeText('Erwarteter Betrag')
        ->assertDontSeeText('Noch nicht final');

    Carbon::setTestNow();
});

it('filters participations and never exposes donor private identity', function (): void {
    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $currentEvent = DonationEvent::factory()->year(2036)->create(['title' => 'Current Event']);
    $previousEvent = DonationEvent::factory()->year(2035)->create(['title' => 'Previous Event']);
    setPortalCurrentEvent($currentEvent);

    $externalUser = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($currentEvent, $externalUser)->create([
        'sport_type_id' => $sportType->id,
        'comment' => 'CURRENT-PARTICIPATION-COMMENT',
    ]);
    AthleteRegistration::factory()->forEvent($previousEvent)->forExternalUser($externalUser)->create([
        'sport_type_id' => $sportType->id,
        'comment' => 'PREVIOUS-PARTICIPATION-COMMENT',
    ]);

    $supporter = ExternalUser::factory()->create([
        'first_name' => 'Pat',
        'last_name' => 'PRIVATE-SURNAME-9X',
        'email' => 'leak-check@example.test',
        'phone_number' => '+41 79 999 99 99',
        'address' => 'SECRET-STREET-91',
        'public_id' => 'ABC234',
    ]);
    Donation::factory()->forPair($supporter, $registration)->create([
        'comment' => 'Viel Erfolg',
        'verified' => false,
    ]);
    $supporter->delete();

    $unrelatedUser = ExternalUser::factory()->create(['first_name' => 'Unrelated', 'last_name' => 'PERSON-7Z']);
    AthleteRegistration::factory()->forVerifiedEventUser($currentEvent, $unrelatedUser)->create(['comment' => 'UNRELATED-RECORD']);

    actingAs($externalUser, 'external');

    get(route('portal.participations', ['anlass' => $currentEvent->slug]))
        ->assertSuccessful()
        ->assertSee('onchange="Livewire.navigate(', false)
        ->assertSee('data-expandable-comment', false)
        ->assertSee('data-expand-comment', false)
        ->assertDontSeeText('Anzeigen')
        ->assertSeeText('Teilnahmen')
        ->assertSeeText('CURRENT-PARTICIPATION-COMMENT')
        ->assertDontSeeText('PREVIOUS-PARTICIPATION-COMMENT')
        ->assertSeeText('Pat P. (ABC-234)')
        ->assertSeeText('Ausstehend')
        ->assertDontSeeText('Bestätigt')
        ->assertSeeText('Viel Erfolg')
        ->assertDontSeeText('PRIVATE-SURNAME-9X')
        ->assertDontSee('leak-check@example.test')
        ->assertDontSeeText('+41 79 999 99 99')
        ->assertDontSeeText('SECRET-STREET-91')
        ->assertDontSeeText('UNRELATED-RECORD')
        ->assertDontSeeText('PERSON-7Z');
});

it('shows donor-only users only their relevant dashboard summary', function (): void {
    $event = DonationEvent::factory()->year(2036)->create();
    setPortalCurrentEvent($event);

    $externalUser = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forEvent($event)->verified()->create();
    Donation::factory()->forPair($externalUser, $registration)->create(['verified' => true]);

    actingAs($externalUser, 'external');

    get(route('portal.dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Deine Unterstützung')
        ->assertSeeText('Spenden (geschätzt)')
        ->assertDontSeeText('Eingegangene Spenden')
        ->assertDontSeeText('Deine Teilnahme')
        ->assertDontSee(route('portal.participations'));
});

it('offers current event registration links in empty states', function (): void {
    $event = DonationEvent::factory()->create([
        'starts_at' => now()->addWeek(),
        'registration_opens_at' => now()->subDay(),
        'athlete_registration_closes_at' => now()->addDay(),
        'donor_registration_closes_at' => now()->addDay(),
    ]);
    setPortalCurrentEvent($event);

    actingAs(ExternalUser::factory()->create(), 'external');

    get(route('portal.dashboard'))
        ->assertSuccessful()
        ->assertSee(route('become-athlete'))
        ->assertSee(route('become-donor'));

    get(route('portal.participations'))
        ->assertSuccessful()
        ->assertSeeText('Noch keine Teilnahme')
        ->assertSee(route('become-athlete'));

    get(route('portal.donations'))
        ->assertSuccessful()
        ->assertSeeText('Noch keine Spende')
        ->assertSee(route('become-donor'));
});

it('promotes material for a confirmed upcoming athlete participation', function (): void {
    $event = DonationEvent::factory()->create([
        'starts_at' => now()->addWeek(),
    ]);
    setPortalCurrentEvent($event);

    $externalUser = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $externalUser)->create();

    actingAs($externalUser, 'external');

    get(route('portal.dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Deine Spendenaktion teilen')
        ->assertSeeText('Nutze persönliche Story-Bilder und Vorlagen, um Spender:innen zu gewinnen.')
        ->assertSee(
            route('portal.participations', ['anlass' => $event->slug]).'#participation-'.$registration->id,
            false,
        );
});

it('filters donations and only exposes athlete privacy name with public id', function (): void {
    $currentEvent = DonationEvent::factory()->year(2036)->create(['title' => 'Current Event']);
    $previousEvent = DonationEvent::factory()->year(2035)->create(['title' => 'Previous Event']);
    setPortalCurrentEvent($currentEvent);

    $externalUser = ExternalUser::factory()->create();
    $athlete = ExternalUser::factory()->create([
        'first_name' => 'Sam',
        'last_name' => 'PRIVATE-ATHLETE-4Q',
        'email' => 'athlete-secret@example.test',
        'phone_number' => '+41 78 888 88 88',
        'address' => 'ATHLETE-SECRET-STREET',
        'public_id' => 'XYZ678',
    ]);
    $currentRegistration = AthleteRegistration::factory()->forVerifiedEventUser($currentEvent, $athlete)->create([
        'rounds_done' => 0,
    ]);
    $previousRegistration = AthleteRegistration::factory()->forEvent($previousEvent)->verified()->create();

    Donation::factory()->forPair($externalUser, $currentRegistration)->create([
        'comment' => 'CURRENT-DONATION-COMMENT',
        'verified' => false,
    ]);
    Donation::factory()->forPair($externalUser, $previousRegistration)->create([
        'comment' => 'PREVIOUS-DONATION-COMMENT',
        'verified' => true,
    ]);
    $athlete->delete();

    actingAs($externalUser, 'external');

    get(route('portal.donations', ['anlass' => $currentEvent->slug]))
        ->assertSuccessful()
        ->assertSee('onchange="Livewire.navigate(', false)
        ->assertDontSeeText('Anzeigen')
        ->assertSeeText('Sam P. (XYZ-678)')
        ->assertDontSeeText('CURRENT-DONATION-COMMENT')
        ->assertSeeText('Spende an Sam P. (XYZ-678) bestätigen')
        ->assertSeeText('Spenden (geschätzt)')
        ->assertDontSeeText('Noch nicht final')
        ->assertDontSeeText('PREVIOUS-DONATION-COMMENT')
        ->assertDontSeeText('PRIVATE-ATHLETE-4Q')
        ->assertDontSee('athlete-secret@example.test')
        ->assertDontSeeText('+41 78 888 88 88')
        ->assertDontSeeText('ATHLETE-SECRET-STREET');
});

it('supports all relevant events and rejects unrelated event filters', function (): void {
    $currentEvent = DonationEvent::factory()->year(2036)->create(['title' => 'Current Event']);
    $previousEvent = DonationEvent::factory()->year(2035)->create(['title' => 'Previous Event']);
    $unrelatedEvent = DonationEvent::factory()->year(2034)->create();
    $unpublishedEvent = DonationEvent::factory()->year(2033)->create(['is_published' => false]);
    setPortalCurrentEvent($currentEvent);

    $externalUser = ExternalUser::factory()->create();
    AthleteRegistration::factory()->forEvent($currentEvent)->forExternalUser($externalUser)->verified()->create(['comment' => 'CURRENT-RECORD']);
    AthleteRegistration::factory()->forEvent($previousEvent)->forExternalUser($externalUser)->verified()->create(['comment' => 'PREVIOUS-RECORD']);
    AthleteRegistration::factory()->forEvent($unpublishedEvent)->forExternalUser($externalUser)->create(['comment' => 'UNPUBLISHED-RECORD']);

    actingAs($externalUser, 'external');

    get(route('portal.participations', ['anlass' => '']))
        ->assertSuccessful()
        ->assertViewHas('selectedEventSlug', null)
        ->assertSeeText('Current Event')
        ->assertSeeText('Previous Event');

    get(route('portal.participations', ['anlass' => $unrelatedEvent->slug]))->assertNotFound();
    get(route('portal.participations', ['anlass' => 'unknown-event']))->assertNotFound();
});

function setPortalCurrentEvent(DonationEvent $event): void
{
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();
}
