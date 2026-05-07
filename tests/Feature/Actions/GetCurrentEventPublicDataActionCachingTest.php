<?php

use App\Actions\GetCurrentEventPublicDataAction;
use App\Models\DonationEvent;
use App\Models\Partner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

it('populates cache on first call and serves subsequent calls from cache', function (): void {
    $event = DonationEvent::factory()->create();
    $partner = Partner::factory()->create();
    $event->partners()->attach($partner->id, ['sort_order' => 1, 'is_published' => true]);

    $cacheKey = 'event_public_data_'.$event->id;

    $this->freezeTime();

    expect(Cache::missing($cacheKey))->toBeTrue();

    DB::enableQueryLog();

    (new GetCurrentEventPublicDataAction)($event);

    expect(DB::getQueryLog())->not->toBeEmpty();
    expect(Cache::has($cacheKey))->toBeTrue();

    // Cached value must be plain arrays — no Eloquent objects
    $cached = Cache::get($cacheKey);
    expect($cached)->toBeArray();
    expect($cached)->toHaveKeys(['partners', 'sponsors', 'faqs']);
    expect($cached['partners'])->toBeArray();
    expect($cached['partners'][0])->toBeArray();

    DB::flushQueryLog();

    // Second call: new instance bypasses once(), but hits cache — no DB queries,
    // and the hydrated result still contains the partner (non-empty cached rows).
    $result = (new GetCurrentEventPublicDataAction)($event);

    expect(DB::getQueryLog())->toBeEmpty();
    expect($result['partners'])->toHaveCount(1);
    expect($result['partners']->first()->id)->toBe($partner->id);

    DB::disableQueryLog();
});

it('serves from cache within the one-minute TTL', function (): void {
    $event = DonationEvent::factory()->create();

    $this->freezeTime();

    (new GetCurrentEventPublicDataAction)($event);

    $this->travel(30)->seconds();

    DB::enableQueryLog();

    (new GetCurrentEventPublicDataAction)($event);

    expect(DB::getQueryLog())->toBeEmpty();

    DB::disableQueryLog();
});

it('re-queries the database after the one-minute TTL expires', function (): void {
    $event = DonationEvent::factory()->create();

    $this->freezeTime();

    (new GetCurrentEventPublicDataAction)($event);

    $this->travel(61)->seconds();

    DB::enableQueryLog();

    (new GetCurrentEventPublicDataAction)($event);

    expect(DB::getQueryLog())->not->toBeEmpty();

    DB::disableQueryLog();
});

it('uses a separate cache key when no event is given', function (): void {
    $cacheKey = 'event_public_data_none';

    $this->freezeTime();

    expect(Cache::missing($cacheKey))->toBeTrue();

    (new GetCurrentEventPublicDataAction)(null);

    $cached = Cache::get($cacheKey);
    expect($cached)->toBeArray();
    expect($cached)->toHaveKeys(['partners', 'sponsors', 'faqs']);
});
