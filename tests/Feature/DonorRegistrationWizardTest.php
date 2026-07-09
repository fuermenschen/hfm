<?php

use App\Components\DonorRegistrationWizard;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use App\Models\User;
use App\Notifications\ConfirmDonorRegistration;
use App\Notifications\ContinueDonorRegistration;
use App\Settings\EventSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function createDonorTestEventWithAthlete(bool $donorRegistrationOpen = false): DonationEvent
{
    $event = DonationEvent::factory()->defaults()->create([
        'registration_opens_at' => $donorRegistrationOpen ? now()->subDay() : now()->addDay(),
        'donor_registration_closes_at' => $donorRegistrationOpen ? now()->addDay() : now()->addDays(2),
    ]);

    $partner = Partner::factory()->create(['name' => 'Brühlgut Stiftung']);
    $event->partners()->attach($partner, ['sort_order' => 1, 'is_published' => true]);

    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $event->sportTypes()->attach($sportType, ['sort_order' => 1, 'is_enabled' => true]);

    $athleteUser = ExternalUser::factory()->create([
        'first_name' => 'Claudia',
        'last_name' => 'Müller',
    ]);

    AthleteRegistration::query()->create([
        'donation_event_id' => $event->id,
        'external_user_id' => $athleteUser->id,
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
        'rounds_estimated' => 10,
        'rounds_done' => 0,
        'comment' => 'Ich laufe für den guten Zweck!',
        'notify_previous_donors' => false,
        'verified' => true,
    ]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    Cache::forget('current_donation_event');

    return $event;
}

it('shows wizard when donor registration is open', function (): void {
    createDonorTestEventWithAthlete(donorRegistrationOpen: true);

    Livewire::test(DonorRegistrationWizard::class)
        ->assertSet('currentStep', 'start')
        ->assertDontSee('Schritt')
        ->assertSee('Mit welcher E-Mail-Adresse möchtest du dich anmelden?');
});

it('creates external user and donation for new donors', function (): void {
    Sleep::fake();
    Notification::fake();

    $event = createDonorTestEventWithAthlete(donorRegistrationOpen: true);
    $athleteRegistration = AthleteRegistration::query()->whereBelongsTo($event)->firstOrFail();

    Livewire::test(DonorRegistrationWizard::class)
        ->assertSet('currentStep', 'start')
        ->assertDontSee('Schritt')
        ->set('returning_email', 'francesca@example.com')
        ->set('returning_email_confirmation', 'francesca@example.com')
        ->call('next')
        ->assertSet('currentStep', 'personal')
        ->assertSet('email', 'francesca@example.com')
        ->assertSee('Schritt 1 von 3')
        ->set('first_name', 'Francesca')
        ->set('last_name', 'Arslan')
        ->set('address', 'Zelglistrasse 41')
        ->set('zip_code', '8406')
        ->set('city', 'Winterthur')
        ->set('country_of_residence', 'CH')
        ->set('phone_country', 'CH')
        ->set('phone_national', '79 123 45 67')
        ->set('email', 'francesca@example.com')
        ->set('email_confirmation', 'francesca@example.com')
        ->call('next')
        ->assertSet('currentStep', 'donation')
        ->assertSee('Schritt 2 von 3')
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 7.50)
        ->set('amount_min', 50.00)
        ->set('amount_max', 200.00)
        ->set('comment', 'Tolle Sache!')
        ->set('privacy_accepted', true)
        ->call('submit')
        ->assertSet('currentStep', 'submitted')
        ->assertSee('Schritt 3 von 3');

    $externalUser = ExternalUser::query()->where('email', 'francesca@example.com')->firstOrFail();
    $donation = Donation::query()->whereBelongsTo($externalUser, 'donorExternalUser')->firstOrFail();

    expect($externalUser->first_name)->toBe('Francesca')
        ->and($externalUser->country_of_residence)->toBe('CH')
        ->and($donation->amount_per_round)->toBe(7.50)
        ->and($donation->amount_min)->toBe(50.00)
        ->and($donation->amount_max)->toBe(200.00)
        ->and($donation->verified)->toBeFalse()
        ->and($donation->athlete_registration_id)->toBe($athleteRegistration->id);

    Notification::assertSentTo($externalUser, ConfirmDonorRegistration::class);
});

it('sends login link when returning email found', function (): void {
    Sleep::fake();
    Notification::fake();

    createDonorTestEventWithAthlete(donorRegistrationOpen: true);
    ExternalUser::factory()->create(['email' => 'francesca@example.com']);

    Livewire::test(DonorRegistrationWizard::class)
        ->set('returning_email', 'francesca@example.com')
        ->set('returning_email_confirmation', 'francesca@example.com')
        ->call('next')
        ->assertSet('currentStep', 'login-link-sent')
        ->assertSet('participation', 'returning')
        ->assertDontSee('Schritt');

    Notification::assertSentTo(
        Notification::route('mail', 'francesca@example.com'),
        ContinueDonorRegistration::class,
    );
});

it('validates amount constraints', function (): void {
    $event = createDonorTestEventWithAthlete(donorRegistrationOpen: true);
    $athleteRegistration = AthleteRegistration::query()->whereBelongsTo($event)->firstOrFail();

    Livewire::test(DonorRegistrationWizard::class)
        ->set('participation', 'new')
        ->set('returning_email', 'francesca@example.com')
        ->set('returning_email_confirmation', 'francesca@example.com')
        ->call('goTo', 'donation')
        ->set('first_name', 'Francesca')
        ->set('last_name', 'Arslan')
        ->set('address', 'Zelglistrasse 41')
        ->set('zip_code', '8406')
        ->set('city', 'Winterthur')
        ->set('country_of_residence', 'CH')
        ->set('phone_country', 'CH')
        ->set('phone_national', '79 123 45 67')
        ->set('email', 'francesca@example.com')
        ->set('email_confirmation', 'francesca@example.com')
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 10.00)
        ->set('amount_min', 5.00)
        ->set('privacy_accepted', true)
        ->call('submit')
        ->assertHasErrors(['amount_min']);
});

it('validates amount max >= amount min on submit', function (): void {
    $event = createDonorTestEventWithAthlete(donorRegistrationOpen: true);
    $athleteRegistration = AthleteRegistration::query()->whereBelongsTo($event)->firstOrFail();

    Livewire::test(DonorRegistrationWizard::class)
        ->set('participation', 'new')
        ->set('returning_email', 'francesca@example.com')
        ->set('returning_email_confirmation', 'francesca@example.com')
        ->call('goTo', 'donation')
        ->set('first_name', 'Francesca')
        ->set('last_name', 'Arslan')
        ->set('address', 'Zelglistrasse 41')
        ->set('zip_code', '8406')
        ->set('city', 'Winterthur')
        ->set('country_of_residence', 'CH')
        ->set('phone_country', 'CH')
        ->set('phone_national', '79 123 45 67')
        ->set('email', 'francesca@example.com')
        ->set('email_confirmation', 'francesca@example.com')
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 5.00)
        ->set('amount_min', 200.00)
        ->set('amount_max', 100.00)
        ->set('privacy_accepted', true)
        ->call('submit')
        ->assertHasErrors(['amount_max']);
});

it('shows athlete context when athlete is selected', function (): void {
    $event = createDonorTestEventWithAthlete(donorRegistrationOpen: true);
    $athleteRegistration = AthleteRegistration::query()
        ->whereBelongsTo($event)
        ->with(['externalUser', 'sportType', 'partner'])
        ->firstOrFail();

    Livewire::test(DonorRegistrationWizard::class)
        ->set('participation', 'new')
        ->call('goTo', 'donation')
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->assertSet('currentAthleteName', $athleteRegistration->externalUser->privacy_name)
        ->assertSet('currentSportType', 'Laufen')
        ->assertSet('currentPartner', 'Brühlgut Stiftung')
        ->assertSet('currentRounds', 10);
});

it('only exposes sanitized athlete fields to Livewire', function (): void {
    $event = createDonorTestEventWithAthlete(donorRegistrationOpen: true);
    $athleteRegistration = AthleteRegistration::query()
        ->whereBelongsTo($event)
        ->with('externalUser')
        ->firstOrFail();

    $component = Livewire::test(DonorRegistrationWizard::class)
        ->set('participation', 'new')
        ->call('goTo', 'donation')
        ->assertSee($athleteRegistration->externalUser->privacy_name)
        ->assertDontSee($athleteRegistration->externalUser->full_name);

    $registrations = $component->get('athleteRegistrations');
    $serializedRegistrations = json_encode($registrations, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

    expect($registrations)->toHaveCount(1)
        ->and(array_keys($registrations[0]))->toBe([
            'id',
            'privacy_name',
            'sport_type',
            'partner',
            'rounds_estimated',
        ])
        ->and($serializedRegistrations)->not->toContain($athleteRegistration->externalUser->last_name)
        ->and($serializedRegistrations)->not->toContain($athleteRegistration->externalUser->email)
        ->and($serializedRegistrations)->not->toContain($athleteRegistration->externalUser->address);
});

it('allows restart after submission for multiple donations', function (): void {
    Sleep::fake();
    Notification::fake();

    $event = createDonorTestEventWithAthlete(donorRegistrationOpen: true);
    $athleteRegistration = AthleteRegistration::query()->whereBelongsTo($event)->firstOrFail();

    Livewire::test(DonorRegistrationWizard::class)
        ->set('participation', 'new')
        ->set('returning_email', 'francesca@example.com')
        ->set('returning_email_confirmation', 'francesca@example.com')
        ->call('goTo', 'donation')
        ->set('first_name', 'Francesca')
        ->set('last_name', 'Arslan')
        ->set('address', 'Zelglistrasse 41')
        ->set('zip_code', '8406')
        ->set('city', 'Winterthur')
        ->set('country_of_residence', 'CH')
        ->set('phone_country', 'CH')
        ->set('phone_national', '79 123 45 67')
        ->set('email', 'francesca@example.com')
        ->set('email_confirmation', 'francesca@example.com')
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 5.00)
        ->set('privacy_accepted', true)
        ->call('submit')
        ->assertSet('currentStep', 'submitted')
        ->assertSee('Weitere:n Sportler:in unterstützen')
        ->call('restart')
        ->assertSet('currentStep', 'start')
        ->assertSet('amount_per_round', null)
        ->assertSet('athlete_registration_id', null);
});

it('skips personal step for authenticated external users', function (): void {
    Sleep::fake();
    Notification::fake();

    $event = createDonorTestEventWithAthlete(donorRegistrationOpen: true);
    $externalUser = ExternalUser::factory()->create();

    actingAs($externalUser, 'external');

    Livewire::test(DonorRegistrationWizard::class)
        ->assertSet('currentStep', 'donation')
        ->assertSet('isAuthenticatedExternalUser', true)
        ->assertSet('participation', 'returning')
        ->assertSee('Schritt 1 von 2');
});

it('restarts on donation step for authenticated external users', function (): void {
    createDonorTestEventWithAthlete(donorRegistrationOpen: true);

    actingAs(ExternalUser::factory()->create(), 'external');

    Livewire::test(DonorRegistrationWizard::class)
        ->set('currentStep', 'submitted')
        ->set('athlete_registration_id', 123)
        ->set('amount_per_round', 5.00)
        ->call('restart')
        ->assertSet('currentStep', 'donation')
        ->assertSet('participation', 'returning')
        ->assertSet('athlete_registration_id', null)
        ->assertSet('amount_per_round', null);
});

it('validates required fields per step', function (): void {
    createDonorTestEventWithAthlete(donorRegistrationOpen: true);

    Livewire::test(DonorRegistrationWizard::class)
        ->set('participation', 'new')
        ->call('goTo', 'personal')
        ->call('next')
        ->assertHasErrors([
            'first_name' => ['required'],
            'last_name' => ['required'],
            'address' => ['required'],
            'zip_code' => ['required'],
            'city' => ['required'],
            'phone_national' => ['required'],
            'email' => ['required'],
        ]);
});

it('validates privacy acceptance on donation step', function (): void {
    $event = createDonorTestEventWithAthlete(donorRegistrationOpen: true);
    $athleteRegistration = AthleteRegistration::query()->whereBelongsTo($event)->firstOrFail();

    Livewire::test(DonorRegistrationWizard::class)
        ->set('participation', 'new')
        ->set('returning_email', 'francesca@example.com')
        ->set('returning_email_confirmation', 'francesca@example.com')
        ->call('goTo', 'donation')
        ->set('first_name', 'Francesca')
        ->set('last_name', 'Arslan')
        ->set('address', 'Zelglistrasse 41')
        ->set('zip_code', '8406')
        ->set('city', 'Winterthur')
        ->set('country_of_residence', 'CH')
        ->set('phone_country', 'CH')
        ->set('phone_national', '79 123 45 67')
        ->set('email', 'francesca@example.com')
        ->set('email_confirmation', 'francesca@example.com')
        ->set('athlete_registration_id', $athleteRegistration->id)
        ->set('amount_per_round', 5.00)
        ->set('privacy_accepted', false)
        ->call('submit')
        ->assertHasErrors(['privacy_accepted']);
});

it('hides wizard for logged in admins', function (): void {
    createDonorTestEventWithAthlete(donorRegistrationOpen: true);

    actingAs(User::factory()->create(), 'web');

    get(route('become-donor'))
        ->assertSuccessful()
        ->assertDontSee('Mit welcher E-Mail-Adresse möchtest du dich anmelden?')
        ->assertSee('Du bist als Admin angemeldet.')
        ->assertSee('privaten Browser-Tab');
});

it('mounts wizard on become-donor page when registration is open and verified athletes exist', function (): void {
    createDonorTestEventWithAthlete(donorRegistrationOpen: true);

    get(route('become-donor'))
        ->assertSuccessful()
        ->assertSee('Mit welcher E-Mail-Adresse möchtest du dich anmelden?')
        ->assertDontSee('Schritt')
        ->assertDontSee('Newsletter Anmeldung');
});

it('shows no-athletes message when registration is open but no verified athletes', function (): void {
    $event = DonationEvent::factory()->defaults()->create([
        'registration_opens_at' => now()->subDay(),
        'donor_registration_closes_at' => now()->addDay(),
    ]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();
    Cache::forget('current_donation_event');

    get(route('become-donor'))
        ->assertSuccessful()
        ->assertSee('Aktuell sind noch keine Sportler:innen angemeldet.')
        ->assertDontSee('Mit welcher E-Mail-Adresse möchtest du dich anmelden?')
        ->assertSee('Newsletter Anmeldung');
});

it('shows closed message when donor registration is not open', function (): void {
    createDonorTestEventWithAthlete(donorRegistrationOpen: false);

    get(route('become-donor'))
        ->assertSuccessful()
        ->assertSee('Die Anmeldung als Spender:in ist aktuell noch nicht offen.')
        ->assertDontSee('Mit welcher E-Mail-Adresse möchtest du dich anmelden?')
        ->assertSee('Newsletter Anmeldung');
});
