<?php

use App\Models\DonationEvent;
use App\Models\Partner;
use App\Models\Sponsor;
use App\Settings\EventSettings;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

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

it('uses default metadata when event SEO content is blank', function (): void {
    travelTo(Date::parse('2026-09-12 14:00:00', 'Europe/Zurich'));
    $event = DonationEvent::factory()->create([
        'is_published' => true,
        'starts_at' => '2026-09-12 12:00:00',
        'ends_at' => '2026-09-12 16:00:00',
        'content' => [
            'seo' => [
                'meta_description_md' => '   ',
                'og_description_md' => '',
            ],
        ],
    ]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    get(route('home'))
        ->assertSuccessful()
        ->assertSee('content="Höhenmeter für Menschen: Ein Spendenlauf in Winterthur für lokale Benefizpartner:innen. Der Anlass findet jetzt statt · Hoehenmeter fuer Menschen in Winterthur am 12. September 2026."', escape: false)
        ->assertSee('content="Ein Spendenlauf in Winterthur für lokale Benefizpartner:innen. Der Anlass findet jetzt statt · Hoehenmeter fuer Menschen in Winterthur am 12. September 2026."', escape: false);
});

it('uses displayed edition for public metadata and operational edition for unrelated pages', function (): void {
    travelTo(Date::parse('2026-09-12 14:00:00', 'Europe/Zurich'));
    $configured = DonationEvent::factory()->create([
        'slug' => 'old',
        'title' => 'Configured Edition',
        'starts_at' => '2026-09-10 12:00:00',
        'ends_at' => '2026-09-11 16:00:00',
        'registration_opens_at' => '2026-09-01 00:00:00',
        'athlete_registration_closes_at' => '2026-09-11 16:00:00',
        'donor_registration_closes_at' => '2026-09-11 16:00:00',
        'content' => ['seo' => [
            'meta_description_md' => 'OPERATIONAL EDITION META',
            'og_description_md' => 'OPERATIONAL OG DESCRIPTION',
        ]],
    ]);
    $displayed = DonationEvent::factory()->create([
        'slug' => 'next',
        'title' => 'Displayed Edition',
        'location_city' => 'Neue Stadt',
        'starts_at' => '2026-09-13 12:00:00',
        'ends_at' => '2026-09-13 16:00:00',
        'content' => ['seo' => [
            'meta_description_md' => 'DISPLAYED EDITION META',
            'og_description_md' => 'DISPLAYED OG DESCRIPTION',
        ]],
    ]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $configured->id;
    $settings->save();

    get(route('home'))
        ->assertSee('<title>Displayed Edition · 2026 - '.config('app.name').'</title>', false)
        ->assertSee('content="DISPLAYED EDITION META Bevorstehender Anlass · Displayed Edition in Neue Stadt am 13. September 2026."', false)
        ->assertSee('content="DISPLAYED OG DESCRIPTION Bevorstehender Anlass · Displayed Edition in Neue Stadt am 13. September 2026."', false)
        ->assertSee('content="'.route('home').'"', false)
        ->assertDontSee('OPERATIONAL EDITION META');

    get(route('contact'))
        ->assertSee('OPERATIONAL EDITION META')
        ->assertDontSee('DISPLAYED EDITION META');
});

it('uses neutral metadata when no published event is available', function (): void {
    $settings = app(EventSettings::class);
    $settings->current_event_id = null;
    $settings->save();

    get(route('home'))
        ->assertSee('<title>'.config('app.name').'</title>', false)
        ->assertSee('content="Höhenmeter für Menschen: Ein Spendenlauf in Winterthur für lokale Benefizpartner:innen."', false)
        ->assertSee('content="Ein Spendenlauf in Winterthur für lokale Benefizpartner:innen."', false)
        ->assertSee('content="'.route('home').'"', false);
});
