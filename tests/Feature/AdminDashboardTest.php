<?php

use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use App\Models\User;
use App\Settings\EventSettings;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('renders the admin dashboard for authenticated users', function () {
    $user = User::factory()->create();

    actingAs($user);

    get('/admin')
        ->assertSuccessful()
        ->assertSee('Sportler:innen')
        ->assertSee('Entwicklung bis zum Anlass')
        ->assertSee('Für diesen Zeitraum sind noch keine Daten vorhanden.')
        ->assertSee('Spenden (tatsächlich)')
        ->assertSee('Letzte Aktivitäten');
});

it('renders partner cards even when partner totals are missing', function () {
    $user = User::factory()->create();
    Partner::factory()->create(['name' => 'Test Partner']);

    actingAs($user);

    get('/admin')
        ->assertSuccessful()
        ->assertSee('Test Partner');
});

it('renders cumulative charts when dashboard has data from multiple days', function (): void {
    Carbon::setTestNow('2026-09-02 12:00:00');
    $event = DonationEvent::factory()->year(2026)->create();
    $sportType = SportType::query()->create(['name' => 'Run']);

    AthleteRegistration::factory()->create([
        'donation_event_id' => $event->id,
        'sport_type_id' => $sportType->id,
        'created_at' => '2026-09-10 15:00:00',
    ]);
    AthleteRegistration::factory()->create([
        'donation_event_id' => $event->id,
        'sport_type_id' => $sportType->id,
        'created_at' => '2026-09-11 15:00:00',
    ]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    actingAs(User::factory()->create());

    get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('Sportler:innen-Registrierungen')
        ->assertSee('Erwartete Spendensumme')
        ->assertSee('scale="linear"', false)
        ->assertSee('tick-values=', false)
        ->assertSee('Heute:')
        ->assertSee('Tag -10')
        ->assertSee('data-today-marker', false)
        ->assertSee('stroke-width="2"', false)
        ->assertSee('stroke-dasharray="4 4"', false)
        ->assertSee('Die vertikale gestrichelte Linie markiert den heutigen Stand.')
        ->assertDontSee('x-data="{ chartWidth:', false)
        ->assertSee('ui-chart', false);

    Carbon::setTestNow();
});

it('defaults dashboard data and links to the current event', function (): void {
    $currentEvent = DonationEvent::factory()->year(2026)->create(['is_published' => true]);
    $otherEvent = DonationEvent::factory()->year(2025)->create(['is_published' => true]);
    ExternalUser::factory()->asAthlete($currentEvent)->create();
    ExternalUser::factory()->count(2)->asAthlete($otherEvent)->create();

    $settings = app(EventSettings::class);
    $settings->current_event_id = $currentEvent->id;
    $settings->save();

    actingAs(User::factory()->create());

    get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertSee('value="'.$currentEvent->slug.'" selected', false)
        ->assertSee('onchange="this.form.submit()"', false)
        ->assertSee(route('admin.athletes.index', ['anlass' => $currentEvent->slug]), false)
        ->assertSee('data-flux-timeline', false);
});

it('filters the dashboard by another event or all events', function (): void {
    $currentEvent = DonationEvent::factory()->year(2026)->create(['is_published' => true]);
    $otherEvent = DonationEvent::factory()->year(2025)->create(['is_published' => true]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $currentEvent->id;
    $settings->save();

    actingAs(User::factory()->create());

    get(route('admin.dashboard', ['anlass' => $otherEvent->slug]))
        ->assertSuccessful()
        ->assertSee('value="'.$otherEvent->slug.'" selected', false)
        ->assertSee(route('admin.donations.index', ['anlass' => $otherEvent->slug]), false);

    get(route('admin.dashboard').'?anlass=')
        ->assertSuccessful()
        ->assertSee('value="" selected', false)
        ->assertSee(route('admin.donors.index').'?anlass=', false);
});

it('rejects invalid dashboard event filters', function (string $eventSlug): void {
    actingAs(User::factory()->create());

    get(route('admin.dashboard', ['anlass' => $eventSlug]))->assertNotFound();
})->with(['invalid', 'missing-event']);
