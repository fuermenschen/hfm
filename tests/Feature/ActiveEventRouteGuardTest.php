<?php

use App\Models\DonationEvent;
use App\Settings\EventSettings;

it('redirects athlete registration to home when no active event exists', function (): void {
    $settings = app(EventSettings::class);
    $settings->current_event_id = null;
    $settings->save();

    $response = $this->get(route('become-athlete'));

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('no_active_event_redirected', true);
});

it('redirects donor registration to home when no active event exists', function (): void {
    $settings = app(EventSettings::class);
    $settings->current_event_id = null;
    $settings->save();

    $response = $this->get(route('become-donor'));

    $response->assertRedirect(route('home'));
    $response->assertSessionHas('no_active_event_redirected', true);
});

it('keeps registration menu items hidden on home when no active event exists', function (): void {
    $settings = app(EventSettings::class);
    $settings->current_event_id = null;
    $settings->save();

    $response = $this->get(route('home'));

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

    $this->get(route('become-athlete'))->assertSuccessful();
    $this->get(route('become-donor'))->assertSuccessful();
});

it('shows faq warning when no active event exists', function (): void {
    $settings = app(EventSettings::class);
    $settings->current_event_id = null;
    $settings->save();

    $response = $this->get(route('questions-and-answers'));

    $response->assertSuccessful();
    $response->assertSee('anlassbezogene Angaben können jedoch fehlen oder nicht aktuell sein');
});
