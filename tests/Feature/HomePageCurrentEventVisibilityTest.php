<?php

use App\Models\DonationEvent;
use App\Settings\EventSettings;

it('shows fallback hero message and hides content sections when no active event is configured', function (): void {
    $settings = app(EventSettings::class);
    $settings->current_event_id = null;
    $settings->save();

    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('Aktuell ist kein Anlass als aktiv konfiguriert.');
    $response->assertSee('Newsletter abonnieren');
    $response->assertDontSee('Um was geht es?');
});

it('shows full home content when current event is published', function (): void {
    $event = DonationEvent::factory()->create([
        'slug' => '2095',
        'is_published' => true,
    ]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('Werde Sportler:in');
    $response->assertSee('Mehr dazu');
});
