<?php

use App\Models\DonationEvent;
use App\Models\Faq;
use App\Settings\EventSettings;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

it('renders questions and answers page with event faqs', function (): void {
    $event = DonationEvent::factory()->create(['is_published' => true]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $faq = Faq::factory()->create(['title' => 'Event Specific FAQ']);
    $event->faqs()->attach($faq->id, [
        'group' => 'general',
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $response = get(route('questions-and-answers'));

    $response->assertOk();
    $response->assertSee('Event Specific FAQ');
    $response->assertSee('prose prose-sm', false);
    $response->assertSee('<div class="text-sm leading-7">', false);
    $response->assertDontSee('<p class="leading-7 text-sm flex flex-col space-y-3">', false);
});

it('renders questions and answers page with global faqs when no event', function (): void {
    $settings = app(EventSettings::class);
    $settings->current_event_id = null;
    $settings->save();

    $faq = Faq::factory()->create(['title' => 'Global FAQ']);

    $response = get(route('questions-and-answers'));

    $response->assertOk();
    $response->assertSee('Global FAQ');
});

it('does not show unpublished faqs on questions and answers', function (): void {
    $event = DonationEvent::factory()->create(['is_published' => true]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $faq = Faq::factory()->create(['title' => 'Hidden FAQ']);
    $event->faqs()->attach($faq->id, [
        'group' => 'general',
        'sort_order' => 1,
        'is_published' => false,
    ]);

    $response = get(route('questions-and-answers'));

    $response->assertOk();
    $response->assertDontSee('Hidden FAQ');
});

it('keeps historical FAQs accessible after the current edition changes', function (): void {
    travelTo(Date::parse('2026-09-12 14:00:00', 'Europe/Zurich'));
    $past = DonationEvent::factory()->create([
        'slug' => 'past',
        'title' => 'Vergangene Ausgabe',
        'starts_at' => '2026-09-11 12:00:00',
        'ends_at' => '2026-09-11 16:00:00',
        'content' => ['seo' => [
            'meta_description_md' => 'HISTORICAL FAQ META',
            'og_description_md' => 'HISTORICAL FAQ OG',
        ]],
    ]);
    $current = DonationEvent::factory()->create([
        'slug' => 'current',
        'title' => 'Aktuelle Ausgabe',
        'starts_at' => '2026-09-13 12:00:00',
        'ends_at' => '2026-09-13 16:00:00',
        'registration_opens_at' => '2026-09-01 00:00:00',
        'athlete_registration_closes_at' => '2026-09-13 16:00:00',
        'donor_registration_closes_at' => '2026-09-14 16:00:00',
        'content' => ['seo' => [
            'meta_description_md' => 'CURRENT FAQ META',
            'og_description_md' => 'CURRENT FAQ OG',
        ]],
    ]);
    $past->faqs()->attach(Faq::factory()->create(['title' => 'Historische Frage']), ['group' => 'general', 'is_published' => true]);
    $current->faqs()->attach(Faq::factory()->create(['title' => 'Aktuelle Frage']), ['group' => 'general', 'is_published' => true]);
    Faq::factory()->create(['title' => 'Allgemeine Frage']);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $current->id;
    $settings->save();

    get(route('questions-and-answers'))
        ->assertSeeText('Aktuelle Frage')
        ->assertDontSeeText('Historische Frage');
    get(route('questions-and-answers.show', ['donationEvent' => 'past']))
        ->assertViewHas('publicDonationEvent', fn (DonationEvent $event): bool => $event->id === $past->id)
        ->assertSeeText('Historische Frage')
        ->assertSeeText('Allgemeine Frage')
        ->assertSeeText('11. September 2026')
        ->assertSeeText('Diese Informationen beziehen sich auf die vergangene Ausgabe.')
        ->assertSee('<title>Fragen und Antworten · 2026 - '.config('app.name').'</title>', false)
        ->assertSee('content="Vergangene Ausgabe in Winterthur am 11. September 2026. Dieser Anlass ist abgeschlossen."', false)
        ->assertSee('name="og:description" content="Vergangene Ausgabe in Winterthur am 11. September 2026. Dieser Anlass ist abgeschlossen."', false)
        ->assertSee('content="'.route('questions-and-answers.show', ['donationEvent' => 'past']).'"', false)
        ->assertDontSee('CURRENT FAQ META')
        ->assertDontSeeText('Aktuelle Frage');

    expect(app(EventSettings::class)->current_event_id)->toBe($current->id);
});

it('returns 404 for unpublished or missing historical FAQ editions', function (string $slug): void {
    DonationEvent::factory()->create(['slug' => 'hidden', 'is_published' => false]);

    get(route('questions-and-answers.show', ['donationEvent' => $slug]))->assertNotFound();
})->with(['unpublished' => 'hidden', 'missing' => 'missing']);
