<?php

use App\Models\DonationEvent;
use App\Models\Partner;
use App\Models\Sponsor;
use App\Settings\EventSettings;
use Database\Seeders\DonationEventSeeder;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\get;
use function Pest\Laravel\seed;
use function Pest\Laravel\travelTo;

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
        'registration_opens_at' => now('Europe/Zurich')->subDay(),
        'athlete_registration_closes_at' => now('Europe/Zurich')->addDay(),
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

it('balances hero partner logos for :dataset', function (int $partnerCount, string $layoutClass): void {
    $event = DonationEvent::factory()->create(['is_published' => true]);
    $partners = Partner::factory()->count($partnerCount)->create();

    foreach ($partners as $index => $partner) {
        $event->partners()->attach($partner, ['sort_order' => $index + 1, 'is_published' => true]);
    }

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $html = get(route('home'))->assertSuccessful()->getContent();

    expect($html)
        ->toContain($layoutClass)
        ->and(substr_count($html, 'aspect-3/1'))->toBe($partnerCount)
        ->and(substr_count($html, 'object-contain'))->toBe($partnerCount * 2);
})->with([
    'one partner' => [1, 'max-w-24 sm:max-w-36'],
    'two partners' => [2, 'max-w-[13.5rem] sm:max-w-[23rem]'],
    'three partners' => [3, 'max-w-84 sm:max-w-[37rem]'],
    'four partners' => [4, 'max-w-[13.5rem] sm:max-w-[23rem]'],
    'five partners' => [5, 'max-w-84 sm:max-w-[37rem]'],
    'six partners' => [6, 'max-w-84 sm:max-w-[37rem]'],
]);

it('uses donor first and athlete fallback for the hero secondary CTA', function (bool $athleteOpen, bool $donorOpen, array $expectedRoutes, array $expectedLabels): void {
    travelTo(Date::parse('2026-09-12 14:00:00', 'Europe/Zurich'));
    $event = DonationEvent::factory()->create([
        'registration_opens_at' => '2026-09-01 00:00:00',
        'athlete_registration_closes_at' => $athleteOpen ? '2026-09-13 00:00:00' : '2026-09-11 00:00:00',
        'donor_registration_closes_at' => $donorOpen ? '2026-09-13 00:00:00' : '2026-09-11 00:00:00',
    ]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $hero = view('components.home-hero', ['currentEventPartners' => collect()])->render();

    preg_match_all('/href="([^"]+)"/', $hero, $links);
    preg_match_all('/(?:Sportler|Spender):in werden/', strip_tags($hero), $labels);
    expect($links[1])->toBe(['#info', ...array_map(fn (string $route): string => route($route), $expectedRoutes)]);
    expect($labels[0])->toBe($expectedLabels);
    expect($hero)->toContain('Mehr dazu');
})->with([
    'both open' => [true, true, ['become-donor'], ['Spender:in werden']],
    'only donor open' => [false, true, ['become-donor'], ['Spender:in werden']],
    'only athlete open' => [true, false, ['become-athlete'], ['Sportler:in werden']],
    'neither open' => [false, false, [], []],
]);

it('shows menu and body invitations only for each open registration window after the event ends', function (bool $athleteOpen, bool $donorOpen): void {
    travelTo(Date::parse('2026-09-12 14:00:00', 'Europe/Zurich'));
    $event = DonationEvent::factory()->create([
        'starts_at' => '2026-09-10 12:00:00',
        'ends_at' => '2026-09-10 16:00:00',
        'registration_opens_at' => '2026-09-01 00:00:00',
        'athlete_registration_closes_at' => $athleteOpen ? '2026-09-13 00:00:00' : '2026-09-11 00:00:00',
        'donor_registration_closes_at' => $donorOpen ? '2026-09-13 00:00:00' : '2026-09-11 00:00:00',
    ]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $response = get(route('home'));

    expect(str_contains($response->getContent(), route('become-athlete')))->toBe($athleteOpen);
    expect(str_contains($response->getContent(), 'Melde dich als Sportler:in!'))->toBe($athleteOpen);
    expect(str_contains($response->getContent(), route('become-donor')))->toBe($donorOpen);
    expect(str_contains($response->getContent(), 'Melde dich als Spender:in!'))->toBe($donorOpen);
})->with([
    'both open' => [true, true],
    'only donor open' => [false, true],
    'only athlete open' => [true, false],
    'neither open' => [false, false],
]);
