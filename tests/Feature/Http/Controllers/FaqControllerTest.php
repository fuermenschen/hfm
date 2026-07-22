<?php

use App\Models\DonationEvent;
use App\Models\Faq;
use App\Settings\EventSettings;

use function Pest\Laravel\get;

it('renders questions and answers page with event faqs', function (): void {
    $event = DonationEvent::factory()->create(['is_published' => true]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $faq = Faq::factory()->create(['title' => 'Event Specific FAQ']);
    $event->faqs()->attach($faq->id, [
        'group' => 'general',
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $response = get(route('questions-and-answers'));

    $response->assertOk();
    $response->assertSee('Event Specific FAQ');
    $response->assertSee('prose prose-sm', false);
    $response->assertSee('<div class="text-sm leading-7">', false);
    $response->assertDontSee('<p class="leading-7 text-sm flex flex-col space-y-3">', false);
});

it('renders questions and answers page with global faqs when no event', function (): void {
    $settings = app(EventSettings::class);
    $settings->current_event_id = null;
    $settings->save();

    $faq = Faq::factory()->create(['title' => 'Global FAQ']);

    $response = get(route('questions-and-answers'));

    $response->assertOk();
    $response->assertSee('Global FAQ');
});

it('does not show unpublished faqs on questions and answers', function (): void {
    $event = DonationEvent::factory()->create(['is_published' => true]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $faq = Faq::factory()->create(['title' => 'Hidden FAQ']);
    $event->faqs()->attach($faq->id, [
        'group' => 'general',
        'sort_order' => 1,
        'is_published' => false,
    ]);

    $response = get(route('questions-and-answers'));

    $response->assertOk();
    $response->assertDontSee('Hidden FAQ');
});
