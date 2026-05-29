<?php

use App\Models\DonationEvent;
use App\Models\Partner;
use App\Models\Sponsor;
use App\Settings\EventSettings;

use function Pest\Laravel\get;

it('renders home page with athlete and donation counts', function (): void {
    $response = get(route('home'));

    $response->assertOk();
});

it('renders home page with partners and sponsors for active event', function (): void {
    $event = DonationEvent::factory()->create(['is_published' => true]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $partner = Partner::factory()->create(['name' => 'Test Partner']);
    $event->partners()->attach($partner->id, ['sort_order' => 1, 'is_published' => true]);

    $sponsor = Sponsor::factory()->create(['name' => 'Test Sponsor']);
    $event->sponsors()->attach($sponsor->id, [
        'size' => 'large',
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $response = get(route('home'));

    $response->assertOk();
    $response->assertSee('Test Partner');
    $response->assertSee('Test Sponsor');
});

it('does not show unpublished partners or sponsors on home', function (): void {
    $event = DonationEvent::factory()->create(['is_published' => true]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $partner = Partner::factory()->create(['name' => 'Hidden Partner']);
    $event->partners()->attach($partner->id, ['sort_order' => 1, 'is_published' => false]);

    $sponsor = Sponsor::factory()->create(['name' => 'Hidden Sponsor']);
    $event->sponsors()->attach($sponsor->id, [
        'size' => 'small',
        'sort_order' => 1,
        'is_published' => false,
    ]);

    $response = get(route('home'));

    $response->assertOk();
    $response->assertDontSee('Hidden Partner');
    $response->assertDontSee('Hidden Sponsor');
});
