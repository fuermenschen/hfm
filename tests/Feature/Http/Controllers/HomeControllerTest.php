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

it('renders home page with event title, partners, and sponsors for active event', function (): void {
    $event = DonationEvent::factory()->create([
        'title' => 'Test Anlass aus der Datenbank',
        'is_published' => true,
    ]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $partner = Partner::factory()->create(['name' => 'Test Partner']);
    $event->partners()->attach($partner->id, ['sort_order' => 1, 'is_published' => true]);

    $sponsor = Sponsor::factory()->create([
        'name' => 'Test Sponsor',
        'description' => 'Generic sponsor description',
    ]);
    $event->sponsors()->attach($sponsor->id, [
        'size' => 'large',
        'contribution_text' => 'Specific event contribution',
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $response = get(route('home'));

    $response->assertOk();
    $response->assertSee('Test Anlass aus der Datenbank');
    $response->assertSee('Test Partner');
    $response->assertSee('Test Sponsor');
    $response->assertSee('Generic sponsor description');
    $response->assertSee('Beitrag an diesem Anlass');
    $response->assertSee('Specific event contribution');
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
        'contribution_text' => 'Hidden contribution',
        'sort_order' => 1,
        'is_published' => false,
    ]);

    $response = get(route('home'));

    $response->assertOk();
    $response->assertDontSee('Hidden Partner');
    $response->assertDontSee('Hidden Sponsor');
});
