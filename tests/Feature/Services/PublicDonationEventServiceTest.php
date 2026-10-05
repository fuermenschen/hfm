<?php

use App\Enums\PublicEventLifecycle;
use App\Models\DonationEvent;
use App\Services\CurrentDonationEventService;
use App\Services\PublicDonationEventService;
use App\Settings\EventSettings;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\travelTo;

it('classifies event timing at start and end boundaries', function (string $time, PublicEventLifecycle $expected): void {
    travelTo(Date::parse($time, 'UTC'));
    $event = DonationEvent::factory()->make([
        'timezone' => 'Europe/Zurich',
        'starts_at' => '2026-09-12 13:00:00',
        'ends_at' => '2026-09-12 16:00:00',
    ]);

    $lifecycle = app(PublicDonationEventService::class)->lifecycle($event);

    expect($lifecycle)->toBe($expected);
})->with([
    'before start' => ['2026-09-12 10:59:59', PublicEventLifecycle::Upcoming],
    'at start' => ['2026-09-12 11:00:00', PublicEventLifecycle::Live],
    'before end' => ['2026-09-12 13:59:59', PublicEventLifecycle::Live],
    'at end' => ['2026-09-12 14:00:00', PublicEventLifecycle::Completed],
]);

it('resolves events using their local timezone rather than the application timezone', function (string $timezone, string $startsAt, string $endsAt): void {
    travelTo(Date::parse('2026-09-12 11:00:00', 'UTC'));
    $event = DonationEvent::factory()->create([
        'timezone' => $timezone,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ]);

    $resolved = app(PublicDonationEventService::class)->resolve();

    expect($resolved['live']?->id)->toBe($event->id);
    expect($resolved['upcoming'])->toBeNull();
    expect($resolved['completed'])->toBeNull();
})->with([
    'Zurich' => ['Europe/Zurich', '2026-09-12 13:00:00', '2026-09-12 16:00:00'],
    'New York' => ['America/New_York', '2026-09-12 07:00:00', '2026-09-12 10:00:00'],
    'Tokyo' => ['Asia/Tokyo', '2026-09-12 20:00:00', '2026-09-12 23:00:00'],
]);

it('selects deterministic editions and orders historical editions by their end date', function (): void {
    travelTo(Date::parse('2026-09-12 14:00:00', 'Europe/Zurich'));
    $events = DonationEvent::factory()->count(9)->sequence(
        ['slug' => 'later-live', 'starts_at' => '2026-09-12 13:00:00', 'ends_at' => '2026-09-12 16:00:00'],
        ['slug' => 'first-live', 'starts_at' => '2026-09-12 12:00:00', 'ends_at' => '2026-09-12 16:00:00'],
        ['slug' => 'tied-live', 'starts_at' => '2026-09-12 12:00:00', 'ends_at' => '2026-09-12 17:00:00'],
        ['slug' => 'later-upcoming', 'starts_at' => '2026-09-14 12:00:00', 'ends_at' => '2026-09-14 16:00:00'],
        ['slug' => 'first-upcoming', 'starts_at' => '2026-09-13 12:00:00', 'ends_at' => '2026-09-13 16:00:00'],
        ['slug' => 'tied-upcoming', 'starts_at' => '2026-09-13 12:00:00', 'ends_at' => '2026-09-13 17:00:00'],
        ['slug' => 'older-completed', 'starts_at' => '2026-09-11 12:00:00', 'ends_at' => '2026-09-11 16:00:00'],
        ['slug' => 'last-completed', 'starts_at' => '2026-09-10 12:00:00', 'ends_at' => '2026-09-12 11:00:00'],
        ['slug' => 'tied-completed', 'starts_at' => '2026-09-12 10:00:00', 'ends_at' => '2026-09-12 11:00:00'],
    )->create();

    $resolved = app(PublicDonationEventService::class)->resolve();

    expect($resolved['live']?->id)->toBe($events[1]->id);
    expect($resolved['upcoming']?->id)->toBe($events[4]->id);
    expect($resolved['completed']?->id)->toBe($events[7]->id);
    expect($resolved['historical']->pluck('slug')->all())->toBe(['last-completed', 'tied-completed', 'older-completed']);
});

it('uses the public page fallback priorities', function (array $editions, string $homepageSlug, string $resultsSlug): void {
    travelTo(Date::parse('2026-09-12 14:00:00', 'Europe/Zurich'));
    DonationEvent::factory()->count(count($editions))->sequence(...$editions)->create();
    $service = app(PublicDonationEventService::class);

    expect($service->homepage()?->slug)->toBe($homepageSlug);
    expect($service->results()?->slug)->toBe($resultsSlug);
})->with([
    'live overrides upcoming and completed' => [[
        ['slug' => 'completed', 'starts_at' => '2026-09-11 12:00:00', 'ends_at' => '2026-09-11 16:00:00'],
        ['slug' => 'upcoming', 'starts_at' => '2026-09-13 12:00:00', 'ends_at' => '2026-09-13 16:00:00'],
        ['slug' => 'live', 'starts_at' => '2026-09-12 12:00:00', 'ends_at' => '2026-09-12 16:00:00'],
    ], 'live', 'live'],
    'homepage promotes next edition while results retain completed edition' => [[
        ['slug' => 'completed', 'starts_at' => '2026-09-11 12:00:00', 'ends_at' => '2026-09-11 16:00:00'],
        ['slug' => 'upcoming', 'starts_at' => '2026-09-13 12:00:00', 'ends_at' => '2026-09-13 16:00:00'],
    ], 'upcoming', 'completed'],
    'only completed edition' => [[
        ['slug' => 'completed', 'starts_at' => '2026-09-11 12:00:00', 'ends_at' => '2026-09-11 16:00:00'],
    ], 'completed', 'completed'],
    'only upcoming edition' => [[
        ['slug' => 'upcoming', 'starts_at' => '2026-09-13 12:00:00', 'ends_at' => '2026-09-13 16:00:00'],
    ], 'upcoming', 'upcoming'],
]);

it('returns no public editions when events are absent or unpublished', function (int $unpublishedCount): void {
    travelTo(Date::parse('2026-09-12 14:00:00', 'Europe/Zurich'));
    DonationEvent::factory()->count($unpublishedCount)->create(['is_published' => false]);
    $service = app(PublicDonationEventService::class);

    $resolved = $service->resolve();

    expect($resolved['live'])->toBeNull();
    expect($resolved['upcoming'])->toBeNull();
    expect($resolved['completed'])->toBeNull();
    expect($resolved['historical'])->toBeEmpty();
    expect($service->homepage())->toBeNull();
    expect($service->results())->toBeNull();
})->with(['no events' => 0, 'only unpublished events' => 3]);

it('keeps registration windows independent of event timing', function (): void {
    travelTo(Date::parse('2026-09-12 14:00:00', 'Europe/Zurich'));
    $upcoming = DonationEvent::factory()->create([
        'starts_at' => '2026-09-13 12:00:00',
        'ends_at' => '2026-09-13 16:00:00',
        'registration_opens_at' => '2026-09-01 12:00:00',
        'athlete_registration_closes_at' => '2026-09-11 12:00:00',
    ]);
    $completed = DonationEvent::factory()->create([
        'starts_at' => '2026-09-11 12:00:00',
        'ends_at' => '2026-09-11 16:00:00',
        'registration_opens_at' => '2026-09-01 12:00:00',
        'donor_registration_closes_at' => '2026-09-20 12:00:00',
    ]);

    $resolved = app(PublicDonationEventService::class)->resolve();

    expect($resolved['upcoming']?->id)->toBe($upcoming->id);
    expect($upcoming->athleteRegistrationIsOpen())->toBeFalse();
    expect($resolved['completed']?->id)->toBe($completed->id);
    expect($completed->donorRegistrationIsOpen())->toBeTrue();
});

it('excludes unpublished editions when choosing among published editions', function (): void {
    travelTo(Date::parse('2026-09-12 14:00:00', 'Europe/Zurich'));
    $published = DonationEvent::factory()->create([
        'starts_at' => '2026-09-10 12:00:00',
        'ends_at' => '2026-09-10 16:00:00',
    ]);
    DonationEvent::factory()->count(3)->sequence(
        ['starts_at' => '2026-09-12 12:00:00', 'ends_at' => '2026-09-12 16:00:00'],
        ['starts_at' => '2026-09-13 12:00:00', 'ends_at' => '2026-09-13 16:00:00'],
        ['starts_at' => '2026-09-11 12:00:00', 'ends_at' => '2026-09-11 16:00:00'],
    )->create(['is_published' => false]);
    $service = app(PublicDonationEventService::class);

    $resolved = $service->resolve();

    expect($resolved['live'])->toBeNull();
    expect($resolved['upcoming'])->toBeNull();
    expect($resolved['historical']->pluck('id')->all())->toBe([$published->id]);
    expect($service->homepage()?->id)->toBe($published->id);
    expect($service->results()?->id)->toBe($published->id);
});

it('reclassifies editions when time advances on the same resolver', function (): void {
    travelTo(Date::parse('2026-09-12 11:59:59', 'Europe/Zurich'));
    $event = DonationEvent::factory()->create([
        'starts_at' => '2026-09-12 12:00:00',
        'ends_at' => '2026-09-12 16:00:00',
    ]);
    $service = app(PublicDonationEventService::class);

    expect($service->resolve()['upcoming']?->id)->toBe($event->id);
    travelTo(Date::parse('2026-09-12 12:00:00', 'Europe/Zurich'));
    expect($service->resolve()['live']?->id)->toBe($event->id);
    travelTo(Date::parse('2026-09-12 16:00:00', 'Europe/Zurich'));
    expect($service->resolve()['completed']?->id)->toBe($event->id);
});

it('reads published editions without changing settings or the operational current event', function (): void {
    travelTo(Date::parse('2026-09-12 14:00:00', 'Europe/Zurich'));
    $configured = DonationEvent::factory()->create([
        'starts_at' => '2026-09-11 12:00:00',
        'ends_at' => '2026-09-11 16:00:00',
    ]);
    $upcoming = DonationEvent::factory()->create([
        'starts_at' => '2026-09-13 12:00:00',
        'ends_at' => '2026-09-13 16:00:00',
    ]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $configured->id;
    $settings->save();
    $settingsBefore = DB::table('settings')->orderBy('id')->get()->toJson();
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $publicEvent = app(PublicDonationEventService::class)->homepage();

    expect($publicEvent?->id)->toBe($upcoming->id);
    expect($queries)->not->toBeEmpty();
    foreach ($queries as $query) {
        expect(strtolower(ltrim($query)))->toStartWith('select');
    }
    expect(DB::table('settings')->orderBy('id')->get()->toJson())->toBe($settingsBefore);
    expect(app(CurrentDonationEventService::class)->current()?->id)->toBe($configured->id);
});
