<?php

use App\Components\AthleteRegistrationWizard;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use App\Notifications\ConfirmAthleteRegistration;
use App\Notifications\ContinueAthleteRegistration;
use App\Settings\EventSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Sleep;
use Livewire\Livewire;

use function Pest\Laravel\get;

it('keeps wizard hidden when athlete registration is closed', function (): void {
    createCurrentEventWithPartner();

    get(route('become-athlete'))
        ->assertSuccessful()
        ->assertDontSee('Wie möchtest du starten?')
        ->assertSee('Newsletter Anmeldung');
});

it('shows wizard when athlete registration is open', function (): void {
    createCurrentEventWithPartner(athleteRegistrationOpen: true);

    get(route('become-athlete'))
        ->assertSuccessful()
        ->assertSee('Wie möchtest du starten?')
        ->assertDontSee('Newsletter Anmeldung');
});

it('creates external user registration and sends confirmation for new participants', function (): void {
    Notification::fake();

    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $event = createCurrentEventWithPartner(athleteRegistrationOpen: true);

    Livewire::test(AthleteRegistrationWizard::class)
        ->assertSet('currentStep', 'start')
        ->set('participation', 'new')
        ->call('next')
        ->assertSet('currentStep', 'personal')
        ->call('next')
        ->assertHasErrors(['first_name' => ['required']])
        ->set('first_name', 'Francesca')
        ->set('last_name', 'Arslan')
        ->set('address', 'Zelglistrasse 41')
        ->set('zip_code', '8406')
        ->set('city', 'Winterthur')
        ->set('phone_number', '079 123 45 67')
        ->set('email', 'francesca@example.com')
        ->set('email_confirmation', 'francesca@example.com')
        ->call('next')
        ->assertSet('currentStep', 'registration')
        ->set('sport_type_id', $sportType->id)
        ->set('rounds_estimated', 12)
        ->set('partner_id', 0)
        ->set('privacy_accepted', true)
        ->call('submit')
        ->assertSet('currentStep', 'submitted')
        ->call('restart')
        ->assertSet('currentStep', 'start')
        ->assertSet('privacy_accepted', false)
        ->assertSet('email', null)
        ->assertSet('sport_type_id', null);

    $externalUser = ExternalUser::query()->where('email', 'francesca@example.com')->firstOrFail();
    $registration = AthleteRegistration::query()
        ->whereBelongsTo($event)
        ->whereBelongsTo($externalUser)
        ->firstOrFail();

    expect($externalUser->first_name)->toBe('Francesca')
        ->and($externalUser->email)->toBe('francesca@example.com')
        ->and($registration->verified)->toBeFalse()
        ->and($registration->partner_id)->toBeNull();

    Notification::assertSentTo($externalUser, ConfirmAthleteRegistration::class);
});

it('blocks new participant submit when email already belongs to an external user', function (): void {
    Notification::fake();

    $sportType = SportType::query()->create(['name' => 'Laufen']);
    createCurrentEventWithPartner(athleteRegistrationOpen: true);
    ExternalUser::factory()->create(['email' => 'francesca@example.com']);

    Livewire::test(AthleteRegistrationWizard::class)
        ->set('participation', 'new')
        ->call('next')
        ->set('first_name', 'Francesca')
        ->set('last_name', 'Arslan')
        ->set('address', 'Zelglistrasse 41')
        ->set('zip_code', '8406')
        ->set('city', 'Winterthur')
        ->set('phone_number', '079 123 45 67')
        ->set('email', 'francesca@example.com')
        ->set('email_confirmation', 'francesca@example.com')
        ->call('next')
        ->set('sport_type_id', $sportType->id)
        ->set('rounds_estimated', 12)
        ->set('partner_id', 0)
        ->set('privacy_accepted', true)
        ->call('submit')
        ->assertHasErrors(['email']);

    expect(ExternalUser::query()->where('email', 'francesca@example.com')->count())->toBe(1)
        ->and(AthleteRegistration::query()->count())->toBe(0);

    Notification::assertNothingSent();
});

it('starts at registration step for logged in external users', function (): void {
    createCurrentEventWithPartner(athleteRegistrationOpen: true);
    $externalUser = ExternalUser::factory()->create(['first_name' => 'Francesca']);

    Livewire::actingAs($externalUser, 'external')
        ->test(AthleteRegistrationWizard::class)
        ->assertSet('currentStep', 'registration')
        ->assertSee('Bestehendes Profil erkannt')
        ->assertDontSee('Wie möchtest du starten?');
});

it('creates unverified registration and sends confirmation notification for logged in external user', function (): void {
    Notification::fake();

    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $event = createCurrentEventWithPartner(athleteRegistrationOpen: true);
    $externalUser = ExternalUser::factory()->create(['first_name' => 'Francesca']);

    Livewire::actingAs($externalUser, 'external')
        ->test(AthleteRegistrationWizard::class)
        ->set('sport_type_id', $sportType->id)
        ->set('rounds_estimated', 12)
        ->set('partner_id', 0)
        ->set('comment', 'Ich freue mich auf den Lauf.')
        ->set('privacy_accepted', true)
        ->call('submit')
        ->assertSet('currentStep', 'submitted');

    $registration = AthleteRegistration::query()
        ->whereBelongsTo($event)
        ->whereBelongsTo($externalUser)
        ->firstOrFail();

    expect($registration->verified)->toBeFalse()
        ->and($registration->partner_id)->toBeNull()
        ->and($registration->rounds_estimated)->toBe(12);

    Notification::assertSentTo(
        $externalUser,
        fn (ConfirmAthleteRegistration $notification): bool => str_contains($notification->confirmationUrl, (string) $registration->id),
    );
});

it('blocks duplicate registration for logged in external user', function (): void {
    Notification::fake();

    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $event = createCurrentEventWithPartner(athleteRegistrationOpen: true);
    $externalUser = ExternalUser::factory()->create();

    AthleteRegistration::factory()->forVerifiedEventUser($event, $externalUser)->create();

    Livewire::actingAs($externalUser, 'external')
        ->test(AthleteRegistrationWizard::class)
        ->set('sport_type_id', $sportType->id)
        ->set('rounds_estimated', 12)
        ->set('partner_id', 0)
        ->set('privacy_accepted', true)
        ->call('submit')
        ->assertHasErrors(['registration'])
        ->assertSee('Bitte logge dich im Portal ein');

    expect(AthleteRegistration::query()->whereBelongsTo($event)->whereBelongsTo($externalUser)->count())->toBe(1);

    Notification::assertNothingSent();
});

it('blocks existing unverified registration for logged in external user', function (): void {
    Notification::fake();

    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $event = createCurrentEventWithPartner(athleteRegistrationOpen: true);
    $externalUser = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->create([
        'donation_event_id' => $event->id,
        'external_user_id' => $externalUser->id,
        'verified' => false,
    ]);

    Livewire::actingAs($externalUser, 'external')
        ->test(AthleteRegistrationWizard::class)
        ->set('sport_type_id', $sportType->id)
        ->set('rounds_estimated', 12)
        ->set('partner_id', 0)
        ->set('privacy_accepted', true)
        ->call('submit')
        ->assertHasErrors(['registration'])
        ->assertSee('Bitte logge dich im Portal ein');

    expect(AthleteRegistration::query()->whereBelongsTo($event)->whereBelongsTo($externalUser)->count())->toBe(1)
        ->and($registration->refresh()->verified)->toBeFalse();

    Notification::assertNothingSent();
});

it('requires privacy consent before registration submission', function (): void {
    Notification::fake();

    $sportType = SportType::query()->create(['name' => 'Laufen']);
    createCurrentEventWithPartner(athleteRegistrationOpen: true);
    $externalUser = ExternalUser::factory()->create();

    Livewire::actingAs($externalUser, 'external')
        ->test(AthleteRegistrationWizard::class)
        ->set('sport_type_id', $sportType->id)
        ->set('rounds_estimated', 12)
        ->set('partner_id', 0)
        ->call('submit')
        ->assertHasErrors(['privacy_accepted' => ['accepted']])
        ->assertSee('Bitte bestätige, dass wir deine Daten für die Organisation des Anlasses verwenden dürfen.');

    expect(AthleteRegistration::query()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('rejects sport types not enabled for the current event', function (): void {
    Notification::fake();

    $enabledSportType = SportType::query()->create(['name' => 'Laufen']);
    $disabledSportType = SportType::query()->create(['name' => 'Velofahren']);
    $event = createCurrentEventWithPartner(athleteRegistrationOpen: true);
    $event->sportTypes()->updateExistingPivot($disabledSportType->id, ['is_enabled' => false]);
    $externalUser = ExternalUser::factory()->create();

    Livewire::actingAs($externalUser, 'external')
        ->test(AthleteRegistrationWizard::class)
        ->assertSee($enabledSportType->name)
        ->assertDontSee($disabledSportType->name)
        ->set('sport_type_id', $disabledSportType->id)
        ->set('rounds_estimated', 12)
        ->set('partner_id', 0)
        ->call('next')
        ->assertHasErrors(['sport_type_id' => ['in']]);

    expect(AthleteRegistration::query()->count())->toBe(0);
});

it('treats soft deleted external user emails as known emails for new participants', function (): void {
    Notification::fake();

    $sportType = SportType::query()->create(['name' => 'Laufen']);
    createCurrentEventWithPartner(athleteRegistrationOpen: true);
    ExternalUser::factory()->create(['email' => 'francesca@example.com'])->delete();

    Livewire::test(AthleteRegistrationWizard::class)
        ->set('participation', 'new')
        ->call('next')
        ->set('first_name', 'Francesca')
        ->set('last_name', 'Arslan')
        ->set('address', 'Zelglistrasse 41')
        ->set('zip_code', '8406')
        ->set('city', 'Winterthur')
        ->set('phone_number', '079 123 45 67')
        ->set('email', 'francesca@example.com')
        ->set('email_confirmation', 'francesca@example.com')
        ->call('next')
        ->set('sport_type_id', $sportType->id)
        ->set('rounds_estimated', 12)
        ->set('partner_id', 0)
        ->set('privacy_accepted', true)
        ->call('submit')
        ->assertHasErrors(['email'])
        ->assertSee('Bitte kontaktiere uns');

    expect(AthleteRegistration::query()->count())->toBe(0);
    Notification::assertNothingSent();
});

it('rate limits returning guest login link requests', function (): void {
    Notification::fake();
    createCurrentEventWithPartner(athleteRegistrationOpen: true);
    ExternalUser::factory()->create(['email' => 'rate-limit@example.com']);
    RateLimiter::clear('athlete-registration-login-link:'.sha1('rate-limit@example.com|127.0.0.1'));

    Livewire::test(AthleteRegistrationWizard::class)
        ->set('participation', 'returning')
        ->set('returning_email', 'rate-limit@example.com')
        ->call('next')
        ->assertSet('currentStep', 'login-link-sent')
        ->call('goTo', 'start')
        ->call('next')
        ->assertSet('currentStep', 'login-link-sent')
        ->call('goTo', 'start')
        ->call('next')
        ->assertHasErrors(['returning_email'])
        ->assertSee('Bitte warte kurz');
});

it('sends login link and stops for returning guest participants', function (): void {
    Notification::fake();
    createCurrentEventWithPartner(athleteRegistrationOpen: true);
    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Francesca',
        'email' => 'francesca@example.com',
    ]);

    Livewire::test(AthleteRegistrationWizard::class)
        ->set('participation', 'returning')
        ->set('returning_email', 'FRANCESCA@example.com')
        ->call('next')
        ->assertSet('currentStep', 'login-link-sent')
        ->assertSee('Login-Link verschickt');

    Notification::assertSentOnDemand(
        ContinueAthleteRegistration::class,
        fn (ContinueAthleteRegistration $notification, array $channels, object $notifiable): bool => $notifiable->routes['mail'] === 'francesca@example.com'
            && str_contains($notification->loginUrl, 'redirect=become-athlete')
            && str_contains($notification->loginUrl, (string) $externalUser->uuid),
    );
});

it('keeps returning guest participants on the login link step until they authenticate', function (): void {
    Notification::fake();

    $sportType = SportType::query()->create(['name' => 'Laufen']);
    createCurrentEventWithPartner(athleteRegistrationOpen: true);
    ExternalUser::factory()->create(['email' => 'francesca@example.com']);

    Livewire::test(AthleteRegistrationWizard::class)
        ->set('participation', 'returning')
        ->set('returning_email', 'francesca@example.com')
        ->call('next')
        ->assertSet('currentStep', 'login-link-sent')
        ->call('goTo', 'registration')
        ->assertSet('currentStep', 'login-link-sent')
        ->set('sport_type_id', $sportType->id)
        ->set('rounds_estimated', 12)
        ->set('partner_id', 0)
        ->call('submit')
        ->assertSet('currentStep', 'login-link-sent')
        ->assertHasErrors(['registration'])
        ->assertSee('Bitte öffne zuerst den Link in deiner E-Mail');

    expect(AthleteRegistration::query()->count())->toBe(0);
});

it('shows same returning guest success state for unknown emails without sending notification', function (): void {
    Sleep::fake();
    Notification::fake();

    createCurrentEventWithPartner(athleteRegistrationOpen: true);

    Livewire::test(AthleteRegistrationWizard::class)
        ->set('participation', 'returning')
        ->set('returning_email', 'unknown@example.com')
        ->call('next')
        ->assertSet('currentStep', 'login-link-sent')
        ->assertSee('Login-Link verschickt');

    Notification::assertNothingSent();
});

function createCurrentEventWithPartner(bool $athleteRegistrationOpen = false): DonationEvent
{
    $event = DonationEvent::factory()->defaults()->create([
        'registration_opens_at' => $athleteRegistrationOpen ? now()->subDay() : now()->addDay(),
        'athlete_registration_closes_at' => $athleteRegistrationOpen ? now()->addDay() : now()->addDays(2),
    ]);
    $partner = Partner::factory()->create(['name' => 'Brühlgut Stiftung']);

    $event->partners()->attach($partner, [
        'sort_order' => 1,
        'is_published' => true,
    ]);

    foreach (SportType::query()->pluck('id') as $sportTypeId) {
        $event->sportTypes()->attach($sportTypeId, [
            'sort_order' => 1,
            'is_enabled' => true,
        ]);
    }

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    Cache::forget('current_donation_event');

    return $event;
}
