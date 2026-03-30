<?php

use App\Models\DonationEvent;
use App\Services\CurrentDonationEventService;
use App\Settings\EventSettings;

it('returns issue when no current event is configured', function (): void {
    $settings = app(EventSettings::class);
    $settings->current_event_id = null;
    $settings->save();

    $service = app(CurrentDonationEventService::class);

    expect($service->current())->toBeNull();
    expect($service->issue())->toBe('missing_current_event');
});

it('returns configured event when it is published', function (): void {
    $event = DonationEvent::factory()->create([
        'slug' => '2090',
        'is_published' => true,
    ]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $service = app(CurrentDonationEventService::class);

    expect($service->current()?->id)->toBe($event->id);
    expect($service->issue())->toBeNull();
});

it('returns issue when configured event ID does not exist in database', function (): void {
    $settings = app(EventSettings::class);
    $settings->current_event_id = 99999;
    $settings->save();

    $service = app(CurrentDonationEventService::class);

    expect($service->current())->toBeNull();
    expect($service->issue())->toBe('current_event_not_found');
});

it('returns issue when configured event is not published', function (): void {
    $event = DonationEvent::factory()->create([
        'slug' => '2091',
        'is_published' => false,
    ]);

    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $service = app(CurrentDonationEventService::class);

    expect($service->current())->toBeNull();
    expect($service->issue())->toBe('current_event_unpublished');
});
