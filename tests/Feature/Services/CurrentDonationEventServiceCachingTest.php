<?php

use App\Models\DonationEvent;
use App\Services\CurrentDonationEventService;
use App\Settings\EventSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// Helper to get a fresh service instance (resets once() memoization)
function freshService(): CurrentDonationEventService
{
    app()->forgetInstance(CurrentDonationEventService::class);

    return app(CurrentDonationEventService::class);
}

it('populates cache on first call and serves subsequent calls from cache', function (): void {
    $event = DonationEvent::factory()->create(['is_published' => true]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $this->freezeTime();

    expect(Cache::missing('current_donation_event'))->toBeTrue();

    DB::enableQueryLog();

    freshService()->current();

    expect(DB::getQueryLog())->not->toBeEmpty();
    expect(Cache::has('current_donation_event'))->toBeTrue();

    // Cached value must be plain scalars — no Eloquent objects
    $cached = Cache::get('current_donation_event');
    expect($cached)->toBeArray();
    expect($cached)->toHaveKey('event_id');
    expect($cached)->toHaveKey('issue');
    expect($cached['event_id'])->toBeInt();

    DB::flushQueryLog();

    // Second call on fresh instance: hits cache, fires one hydration query (DonationEvent::find)
    // but does NOT fire the settings + event-lookup queries
    freshService()->current();

    // Only the hydration PK query fires, not the full resolution logic
    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(1);

    DB::disableQueryLog();
});

it('serves from cache within the one-minute TTL', function (): void {
    $event = DonationEvent::factory()->create(['is_published' => true]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $this->freezeTime();

    freshService()->current();

    $this->travel(30)->seconds();

    DB::enableQueryLog();
    DB::flushQueryLog();

    freshService()->current();

    // Only the hydration PK lookup fires, not the full resolution queries
    expect(count(DB::getQueryLog()))->toBeLessThanOrEqual(1);

    DB::disableQueryLog();
});

it('re-queries the database after the one-minute TTL expires', function (): void {
    $event = DonationEvent::factory()->create(['is_published' => true]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $this->freezeTime();

    freshService()->current();

    $this->travel(61)->seconds();

    DB::enableQueryLog();
    DB::flushQueryLog();

    freshService()->current();

    // After TTL expiry, the full resolution queries fire again (settings + event lookup + hydration)
    expect(count(DB::getQueryLog()))->toBeGreaterThan(1);

    DB::disableQueryLog();
});
