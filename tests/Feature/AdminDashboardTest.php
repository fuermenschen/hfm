<?php

use App\Actions\GetDashboardDataAction;
use App\Components\AdminDashboardContent;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Models\SportType;
use App\Models\User;
use App\Settings\EventSettings;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('renders the admin dashboard shell before loading expensive content', function (): void {
    $user = User::factory()->create();
    $getDashboardData = Mockery::mock(GetDashboardDataAction::class);
    $getDashboardData->shouldNotReceive('__invoke');
    app()->instance(GetDashboardDataAction::class, $getDashboardData);

    actingAs($user);

    get('/admin')
        ->assertSuccessful()
        ->assertSee('Sportler:innen')
        ->assertSee('animate-pulse', false)
        ->assertSee('Statistiken werden nach dem Laden der Seite berechnet.')
        ->assertDontSee('Entwicklung rund um den Anlass')
        ->assertDontSee('Spenden (bezahlt)');
});

it('renders partner cards even when partner totals are missing', function () {
    $user = User::factory()->create();
    Partner::factory()->create(['name' => 'Test Partner']);

    actingAs($user);

    Livewire::test(AdminDashboardContent::class)
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

    Livewire::test(AdminDashboardContent::class, ['eventSlug' => $event->slug])
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
        ->assertSee('wire:snapshot', false);
    Livewire::test(AdminDashboardContent::class, ['eventSlug' => $currentEvent->slug])
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
        ->assertSee('value="'.$otherEvent->slug.'" selected', false);
    Livewire::test(AdminDashboardContent::class, ['eventSlug' => $otherEvent->slug])
        ->assertSee(route('admin.donations.index', ['anlass' => $otherEvent->slug]), false);

    get(route('admin.dashboard').'?anlass=')
        ->assertSuccessful()
        ->assertSee('value="" selected', false)
        ->assertSee('wire:snapshot', false);
    Livewire::test(AdminDashboardContent::class)
        ->assertSee(route('admin.donors.index').'?anlass=', false);
});

it('shows the selected event group count', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    $otherEvent = DonationEvent::factory()->year(2025)->create();
    EventGroup::factory()->count(2)->forEvent($event)->create();
    EventGroup::factory()->forEvent($otherEvent)->create();

    actingAs(User::factory()->create());

    Livewire::test(AdminDashboardContent::class, ['eventSlug' => $event->slug])
        ->assertViewHas('eventGroupCount', 2)
        ->assertSeeText('Gruppen')
        ->assertSeeText('Registriert')
        ->assertSeeText('2');
});

it('rejects invalid dashboard event filters', function (string $eventSlug): void {
    actingAs(User::factory()->create());

    get(route('admin.dashboard', ['anlass' => $eventSlug]))->assertNotFound();
})->with(['invalid', 'missing-event']);

it('renders persisted payment totals for the selected event and all events without contacting Webling', function (): void {
    $event = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();
    $partner = Partner::factory()->create(['name' => 'Cash partner']);
    $event->partners()->attach($partner->id);
    foreach ([$event, $otherEvent] as $invoiceEvent) {
        DonorEventInvoice::factory()->forEvent($invoiceEvent)->create([
            'webling_debitor_id' => 42, 'webling_state' => 'paid', 'webling_remaining_cents' => 0,
            'webling_total_cents' => 10000, 'webling_received_cents' => 11000,
            'webling_excess_cents' => 1000, 'webling_writeoff_cents' => 0,
            'webling_payment_verification' => 'verified', 'webling_payment_date' => '2026-10-01',
            'webling_synced_at' => now(),
            'source_snapshot' => ['lines' => [['partner_id' => $partner->id, 'total_cents' => 10000]]],
        ]);
    }
    actingAs(User::factory()->create());
    Http::preventStrayRequests();

    Livewire::test(AdminDashboardContent::class, ['eventSlug' => $event->slug])
        ->assertViewHas('paymentSummary', fn (array $summary): bool => $summary['received_cents'] === 11000)
        ->assertSee('Fr. 110.00')->assertSee('Mehrzahlungen (verifiziert)');
    Livewire::test(AdminDashboardContent::class)
        ->assertViewHas('paymentSummary', fn (array $summary): bool => $summary['received_cents'] === 22000);
    Http::assertNothingSent();
});
