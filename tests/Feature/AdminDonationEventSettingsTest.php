<?php

use App\Components\AdminDonationEventForm;
use App\Components\AdminDonationEventTable;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\User;
use App\Settings\EventSettings;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('protects donation event settings routes', function (): void {
    $donationEvent = DonationEvent::factory()->create();

    get(route('admin.donation-events.create'))->assertRedirect(route('login'));
    get(route('admin.donation-events.edit', $donationEvent))->assertRedirect(route('login'));

    actingAs(ExternalUser::factory()->create(), 'external');

    get(route('admin.donation-events.create'))->assertRedirect(route('login'));
    get(route('admin.donation-events.edit', $donationEvent))->assertRedirect(route('login'));
});

it('renders create and edit settings pages for admins', function (): void {
    $donationEvent = DonationEvent::factory()->create();

    actingAs(User::factory()->create());

    get(route('admin.donation-events.create'))
        ->assertSuccessful()
        ->assertSee('Anlass erstellen')
        ->assertSee('Öffentliche Inhalte')
        ->assertSee('Erscheint als Haupttitel im Hero der Startseite')
        ->assertSeeInOrder(['Titel', 'Slug'])
        ->assertSeeInOrder(['Startseite', 'Resultate', 'SEO / Teilen', 'Rechnung'])
        ->assertSee('Leere Anmeldedaten halten die entsprechende Anmeldung geschlossen')
        ->assertSee('inputmode="numeric"', escape: false)
        ->assertSee('type="url"', escape: false)
        ->assertSee('overflow-x-clip', escape: false)
        ->assertSee('Formularangaben')
        ->assertSee('Markdown-Syntax')
        ->assertDontSee('form.timezone', escape: false);

    get(route('admin.donation-events.edit', $donationEvent))
        ->assertSuccessful()
        ->assertSee($donationEvent->title)
        ->assertSee('Änderungen speichern');

    get(route('admin.donation-events.edit', 999999))->assertNotFound();
});

it('shows current and publication status in the page header', function (): void {
    $donationEvent = DonationEvent::factory()->create(['is_published' => true]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $donationEvent->id;
    $settings->save();

    actingAs(User::factory()->create());

    get(route('admin.donation-events.edit', $donationEvent))
        ->assertSuccessful()
        ->assertSee('Aktueller Anlass')
        ->assertSee('Veröffentlicht');
});

it('requires confirmation before unpublishing the current event', function (): void {
    $donationEvent = DonationEvent::factory()->create(['is_published' => true]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $donationEvent->id;
    $settings->save();

    actingAs(User::factory()->create());

    $component = Livewire::test(AdminDonationEventForm::class, [
        'donationEvent' => $donationEvent,
        'isCurrentEvent' => true,
    ])
        ->set('form.is_published', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($donationEvent->refresh()->is_published)->toBeTrue();

    $component->call('confirmUnpublished')->assertHasNoErrors();

    expect($donationEvent->refresh()->is_published)->toBeFalse();
});

it('links to event creation and editing from the table', function (): void {
    $donationEvent = DonationEvent::factory()->create();

    Livewire::test(AdminDonationEventTable::class)
        ->assertSee('Neu')
        ->assertSee(route('admin.donation-events.create'), escape: false)
        ->assertSee(route('admin.donation-events.edit', $donationEvent), escape: false);
});

it('creates an event and redirects to its settings page', function (): void {
    actingAs(User::factory()->create());

    $component = Livewire::test(AdminDonationEventForm::class)
        ->set('form', validDonationEventSettingsForm())
        ->call('save')
        ->assertHasNoErrors();

    $donationEvent = DonationEvent::query()->where('slug', '2027')->firstOrFail();

    $component->assertRedirect(route('admin.donation-events.edit', $donationEvent));

    expect($donationEvent->getRawOriginal('starts_at'))->toBe('2027-09-11 11:00:05')
        ->and($donationEvent->timezone)->toBe('Europe/Zurich')
        ->and($donationEvent->is_published)->toBeFalse()
        ->and($donationEvent->has_equal_split_option)->toBeTrue()
        ->and(data_get($donationEvent->content, 'hero.copy_md'))->toBe('Hero 2027')
        ->and(data_get($donationEvent->content, 'invoice.additional_information'))->toBe('Rechnung 2027');

    Livewire::test(AdminDonationEventForm::class, ['donationEvent' => $donationEvent])
        ->assertSet('form.starts_at', '2027-09-11T13:00:05');
});

it('updates event data while preserving unknown content', function (): void {
    $donationEvent = DonationEvent::factory()->create([
        'slug' => '2026',
        'timezone' => 'UTC',
        'content' => [
            'hero' => ['copy_md' => 'Alt'],
            'future' => ['untouched' => 'Wert'],
        ],
    ]);

    actingAs(User::factory()->create());

    Livewire::test(AdminDonationEventForm::class, ['donationEvent' => $donationEvent])
        ->set('form', validDonationEventSettingsForm([
            'slug' => '2028',
            'title' => 'Neuer Titel',
            'is_published' => true,
        ]))
        ->call('save')
        ->assertHasNoErrors();

    $donationEvent->refresh();

    expect($donationEvent->slug)->toBe('2028')
        ->and($donationEvent->title)->toBe('Neuer Titel')
        ->and($donationEvent->timezone)->toBe('Europe/Zurich')
        ->and($donationEvent->is_published)->toBeTrue()
        ->and(data_get($donationEvent->content, 'hero.copy_md'))->toBe('Hero 2027')
        ->and(data_get($donationEvent->content, 'future.untouched'))->toBe('Wert');
});

it('allows an event without a registration schedule', function (): void {
    actingAs(User::factory()->create());

    Livewire::test(AdminDonationEventForm::class)
        ->set('form', validDonationEventSettingsForm([
            'registration_opens_at' => '',
            'athlete_registration_closes_at' => '',
            'donor_registration_closes_at' => '',
        ]))
        ->call('save')
        ->assertHasNoErrors();

    $donationEvent = DonationEvent::query()->where('slug', '2027')->firstOrFail();

    expect($donationEvent->registration_opens_at)->toBeNull()
        ->and($donationEvent->athlete_registration_closes_at)->toBeNull()
        ->and($donationEvent->donor_registration_closes_at)->toBeNull();
});

it('validates event identity, URLs, and date order', function (): void {
    DonationEvent::factory()->create(['slug' => 'duplicate']);

    actingAs(User::factory()->create());

    Livewire::test(AdminDonationEventForm::class)
        ->set('form', validDonationEventSettingsForm([
            'slug' => 'duplicate',
            'ends_at' => '2027-09-11T12:00:05',
            'athlete_registration_closes_at' => '2027-01-31T23:59:59',
            'location_url' => 'not-a-url',
        ]))
        ->call('save')
        ->assertHasErrors([
            'form.slug' => 'unique',
            'form.ends_at' => 'after',
            'form.athlete_registration_closes_at' => 'after_or_equal',
            'form.location_url' => 'url',
        ]);
});

it('rejects unauthenticated event mutations', function (): void {
    Livewire::test(AdminDonationEventForm::class)
        ->set('form', validDonationEventSettingsForm())
        ->call('save')
        ->assertForbidden();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validDonationEventSettingsForm(array $overrides = []): array
{
    return array_replace([
        'title' => 'Höhenmeter für Menschen',
        'slug' => '2027',
        'starts_at' => '2027-09-11T13:00:05',
        'ends_at' => '2027-09-11T18:00:06',
        'registration_opens_at' => '2027-02-01T00:00:01',
        'athlete_registration_closes_at' => '2027-09-11T14:00:02',
        'donor_registration_closes_at' => '2027-09-19T23:59:59',
        'location_name' => 'Brühlgut Stiftung',
        'location_street' => 'Brühlbergstrasse 6',
        'location_postal_code' => '8400',
        'location_city' => 'Winterthur',
        'location_url' => 'https://s.geo.admin.ch/example',
        'is_published' => false,
        'has_equal_split_option' => true,
        'content' => [
            'hero' => ['copy_md' => 'Hero 2027'],
            'home' => [
                'about_heading' => 'Um was geht es?',
                'about_intro_md' => 'Einleitung',
                'about_body_md' => 'Haupttext',
            ],
            'results' => ['heading_md' => 'Resultate 2027'],
            'seo' => [
                'meta_description_md' => 'Meta 2027',
                'og_description_md' => 'OpenGraph 2027',
            ],
            'invoice' => ['additional_information' => 'Rechnung 2027'],
        ],
    ], $overrides);
}
