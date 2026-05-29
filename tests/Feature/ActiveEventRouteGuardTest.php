<?php

use App\Models\DonationEvent;
use App\Models\Faq;
use App\Settings\EventSettings;
use Database\Seeders\DonationEventSeeder;
use Database\Seeders\EventContentBackfillSeeder;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\get;
use function Pest\Laravel\seed;

it('redirects athlete registration to home when no active event exists', function (): void {
    $settings = app(EventSettings::class);
    $settings->current_event_id = null;
    $settings->save();

    $response = get(route('become-athlete'));

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('no_active_event_redirected', true);
});

it('redirects donor registration to home when no active event exists', function (): void {
    $settings = app(EventSettings::class);
    $settings->current_event_id = null;
    $settings->save();

    $response = get(route('become-donor'));

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('no_active_event_redirected', true);
});

it('keeps registration menu items hidden on home when no active event exists', function (): void {
    $settings = app(EventSettings::class);
    $settings->current_event_id = null;
    $settings->save();

    $response = get(route('home'));

    $response->assertSuccessful();
    $response->assertDontSee('Sportler:in werden');
    $response->assertDontSee('Spender:in werden');
});

it('allows registration pages when current event is published', function (): void {
    $event = DonationEvent::factory()->create([
        'slug' => '2099',
        'is_published' => true,
    ]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    get(route('become-athlete'))->assertSuccessful();
    get(route('become-donor'))->assertSuccessful();
});

it('shows faq warning when no active event exists', function (): void {
    $settings = app(EventSettings::class);
    $settings->current_event_id = null;
    $settings->save();

    $response = get(route('questions-and-answers'));

    $response->assertSuccessful();
    $response->assertSee('anlassbezogene Angaben können jedoch fehlen oder nicht aktuell sein');
});

it('renders event timing faq content from faq model', function (): void {
    seed(DonationEventSeeder::class);
    seed(EventContentBackfillSeeder::class);

    $event = DonationEvent::query()->where('slug', '2026')->firstOrFail();
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $response = get(route('questions-and-answers'));

    $response->assertSuccessful();
    $response->assertSee('13 Uhr bis 16 Uhr');
});

it('does not leak event-specific faqs from other events', function (): void {
    seed(DonationEventSeeder::class);
    seed(EventContentBackfillSeeder::class);

    $event2025 = DonationEvent::query()->where('slug', '2025')->firstOrFail();
    $event2026 = DonationEvent::query()->where('slug', '2026')->firstOrFail();

    $faq = Faq::query()->create([
        'title' => 'Nur 2025 FAQ',
        'content_md' => 'Diese FAQ darf nur bei 2025 erscheinen.',
    ]);

    DB::table('donation_event_faq')->insert([
        'donation_event_id' => $event2025->id,
        'faq_id' => $faq->id,
        'group' => 'general',
        'sort_order' => 999,
        'is_published' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event2026->id;
    $settings->save();

    $response = get(route('questions-and-answers'));

    $response->assertSuccessful();
    $response->assertDontSee('Nur 2025 FAQ');
});

it('shows globally unassigned faqs as fallback', function (): void {
    seed(DonationEventSeeder::class);
    seed(EventContentBackfillSeeder::class);

    $event2026 = DonationEvent::query()->where('slug', '2026')->firstOrFail();

    Faq::query()->create([
        'title' => 'Globale FAQ ohne Event',
        'content_md' => 'Diese FAQ ist global sichtbar.',
    ]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event2026->id;
    $settings->save();

    $response = get(route('questions-and-answers'));

    $response->assertSuccessful();
    $response->assertSee('Globale FAQ ohne Event');
});
