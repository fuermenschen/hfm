<?php

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use App\Settings\EventSettings;

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

    $otherCurrentRegistration = AthleteRegistration::factory()->forEvent($currentEvent)->verified()->create();
    Donation::factory()->forPair($externalUser, $otherCurrentRegistration)->create(['verified' => true]);
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
        ->assertViewHas('hasCompletedRounds', true)
        ->assertViewHas('ownDonationCount', 1)
        ->assertSeeText('Effektiver Spendenbetrag')
        ->assertSeeText('Anmeldung bestätigen')
        ->assertSeeText('Previous Event')
        ->assertSeeText('Velofahren · 12 geschätzte Runden · Stiftung Test')
        ->assertSee('wire:click="confirm"', false)
        ->assertSeeText('Erwarteter Betrag Fr. 30.00 · Maximalbetrag Fr. 30.00')
        ->assertSee('onchange="Livewire.navigate(', false)
        ->assertDontSeeText('Anzeigen')
        ->assertSeeText('Current Event')
        ->assertDontSeeText('Unrelated Event');
});

it('hides effective donation amount until rounds are completed', function (): void {
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
        ->assertViewHas('hasCompletedRounds', false)
        ->assertDontSeeText('Effektiver Spendenbetrag')
        ->assertDontSeeText('Aktueller Spendenbetrag');
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
        ->assertDontSeeText('Anzeigen')
        ->assertSeeText('Teilnahmen')
        ->assertSeeText('CURRENT-PARTICIPATION-COMMENT')
        ->assertDontSeeText('PREVIOUS-PARTICIPATION-COMMENT')
        ->assertSeeText('Pat P. (ABC-234)')
        ->assertSeeText('Ausstehend')
        ->assertSeeText('Viel Erfolg')
        ->assertDontSeeText('PRIVATE-SURNAME-9X')
        ->assertDontSee('leak-check@example.test')
        ->assertDontSeeText('+41 79 999 99 99')
        ->assertDontSeeText('SECRET-STREET-91')
        ->assertDontSeeText('UNRELATED-RECORD')
        ->assertDontSeeText('PERSON-7Z');
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
    $currentRegistration = AthleteRegistration::factory()->forVerifiedEventUser($currentEvent, $athlete)->create();
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
        ->assertSeeText('CURRENT-DONATION-COMMENT')
        ->assertSeeText('Bestätigung ausstehend')
        ->assertDontSeeText('PREVIOUS-DONATION-COMMENT')
        ->assertDontSeeText('PRIVATE-ATHLETE-4Q')
        ->assertDontSee('athlete-secret@example.test')
        ->assertDontSeeText('+41 78 888 88 88')
        ->assertDontSeeText('ATHLETE-SECRET-STREET');
});

it('supports all relevant events and rejects unrelated event filters', function (): void {
    $currentEvent = DonationEvent::factory()->year(2036)->create();
    $previousEvent = DonationEvent::factory()->year(2035)->create();
    $unrelatedEvent = DonationEvent::factory()->year(2034)->create();
    $unpublishedEvent = DonationEvent::factory()->year(2033)->create(['is_published' => false]);
    setPortalCurrentEvent($currentEvent);

    $externalUser = ExternalUser::factory()->create();
    AthleteRegistration::factory()->forEvent($currentEvent)->forExternalUser($externalUser)->create(['comment' => 'CURRENT-RECORD']);
    AthleteRegistration::factory()->forEvent($previousEvent)->forExternalUser($externalUser)->create(['comment' => 'PREVIOUS-RECORD']);
    AthleteRegistration::factory()->forEvent($unpublishedEvent)->forExternalUser($externalUser)->create(['comment' => 'UNPUBLISHED-RECORD']);

    actingAs($externalUser, 'external');

    get(route('portal.participations', ['anlass' => '']))
        ->assertSuccessful()
        ->assertViewHas('selectedEventSlug', null)
        ->assertSeeText('CURRENT-RECORD')
        ->assertSeeText('PREVIOUS-RECORD')
        ->assertDontSeeText('UNPUBLISHED-RECORD');

    get(route('portal.participations', ['anlass' => $unrelatedEvent->slug]))->assertNotFound();
    get(route('portal.participations', ['anlass' => 'unknown-event']))->assertNotFound();
});

function setPortalCurrentEvent(DonationEvent $event): void
{
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();
}
