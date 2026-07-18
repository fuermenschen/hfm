<?php

use App\Models\DonationEvent;
use App\Models\Partner;
use App\Models\Sponsor;
use App\Settings\EventSettings;
use Database\Seeders\DonationEventSeeder;

use function Pest\Laravel\get;
use function Pest\Laravel\seed;

it('shows fallback hero message and hides content sections when no active event is configured', function (): void {
    $settings = app(EventSettings::class);
    $settings->current_event_id = null;
    $settings->save();

    $response = get(route('home'));

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

    $response = get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('Werde Sportler:in');
    $response->assertSee('Mehr dazu');
});

it('shows only 2026 partner set and no sponsors on home', function (): void {
    seed(DonationEventSeeder::class);

    $event2025 = DonationEvent::query()->where('slug', '2025')->firstOrFail();
    $event2026 = DonationEvent::query()->where('slug', '2026')->firstOrFail();
    $bruehlgut = Partner::factory()->create([
        'name' => 'Brühlgut Stiftung',
        'beneficiary_blurb' => 'Die Brühlgut Stiftung begleitet und fördert Menschen mit Beeinträchtigung.',
    ]);
    $institute = Partner::factory()->create(['name' => 'Institut Kinderseele Schweiz']);
    $helpline = Partner::factory()->create(['name' => 'Tel. 143 - Die Dargebotene Hand']);

    $event2026->partners()->attach($bruehlgut, ['sort_order' => 1, 'is_published' => true]);
    $event2025->partners()->attach($institute, ['sort_order' => 1, 'is_published' => true]);
    $event2025->partners()->attach($helpline, ['sort_order' => 2, 'is_published' => true]);
    $event2025->sponsors()->attach(Sponsor::factory()->create(), [
        'size' => 'medium',
        'contribution_text' => 'Event contribution',
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event2026->id;
    $settings->save();

    $response = get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('Logo Brühlgut Stiftung');
    $response->assertDontSee('Logo Institut Kinderseele Schweiz');
    $response->assertDontSee('Logo Tel. 143 - Die Dargebotene Hand');
    $response->assertDontSee('Sponsor:innen');
    $response->assertSee('Die Brühlgut Stiftung begleitet und fördert Menschen mit Beeinträchtigung.');
    $response->assertDontSee('Aktuell sind keine Benefizpartner:innen für diesen Anlass publiziert.');
});

it('shows 2025 partners and sponsors on home', function (): void {
    seed(DonationEventSeeder::class);

    $event = DonationEvent::query()->where('slug', '2025')->firstOrFail();
    $partners = collect([
        ['name' => 'Brühlgut Stiftung'],
        [
            'name' => 'Institut Kinderseele Schweiz',
            'beneficiary_blurb' => 'Das Institut Kinderseele Schweiz unterstützt Kinder psychisch erkrankter Eltern.',
        ],
        ['name' => 'Tel. 143 - Die Dargebotene Hand'],
    ])->map(fn (array $attributes): Partner => Partner::factory()->create($attributes));

    foreach ($partners as $index => $partner) {
        $event->partners()->attach($partner, ['sort_order' => $index + 1, 'is_published' => true]);
    }

    foreach (['Rohner Spiller', 'TM Kommunikation', 'Veloplus', 'Intersport Egli'] as $index => $name) {
        $event->sponsors()->attach(Sponsor::factory()->create(['name' => $name]), [
            'size' => 'medium',
            'contribution_text' => 'Event contribution',
            'sort_order' => $index + 1,
            'is_published' => true,
        ]);
    }

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $response = get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('Logo Brühlgut Stiftung');
    $response->assertSee('Logo Institut Kinderseele Schweiz');
    $response->assertSee('Logo Tel. 143 - Die Dargebotene Hand');
    $response->assertSee('Rohner Spiller Logo');
    $response->assertSee('TM Kommunikation Logo');
    $response->assertSee('Veloplus Logo');
    $response->assertSee('Intersport Egli Logo');
    $response->assertSee('Das Institut Kinderseele Schweiz unterstützt Kinder psychisch erkrankter Eltern.');
});
