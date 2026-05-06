<?php

use App\Actions\GetCurrentEventPublicDataAction;
use App\Models\DonationEvent;
use App\Models\Faq;
use App\Models\Partner;
use App\Models\Sponsor;

it('returns empty collections when no event is given', function (): void {
    $action = new GetCurrentEventPublicDataAction;

    $result = $action(null);

    expect($result['partners'])->toBeEmpty();
    expect($result['sponsors'])->toBeEmpty();
    expect($result['faqs'])->toBeEmpty();
});

it('returns published partners sorted by pivot order and name', function (): void {
    $event = DonationEvent::factory()->create();
    $partnerA = Partner::factory()->create(['name' => 'Alpha']);
    $partnerB = Partner::factory()->create(['name' => 'Beta']);
    $partnerC = Partner::factory()->create(['name' => 'Charlie']);

    $event->partners()->attach($partnerA->id, ['sort_order' => 2, 'is_published' => true]);
    $event->partners()->attach($partnerB->id, ['sort_order' => 1, 'is_published' => true]);
    $event->partners()->attach($partnerC->id, ['sort_order' => 3, 'is_published' => false]);

    $action = new GetCurrentEventPublicDataAction;
    $result = $action($event);

    expect($result['partners']->pluck('id')->all())->toBe([$partnerB->id, $partnerA->id]);
    expect($result['sponsors'])->toBeEmpty();
});

it('returns published sponsors with size attribute', function (): void {
    $event = DonationEvent::factory()->create();
    $sponsor = Sponsor::factory()->create();

    $event->sponsors()->attach($sponsor->id, [
        'size' => 'large',
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $action = new GetCurrentEventPublicDataAction;
    $result = $action($event);

    expect($result['sponsors'])->toHaveCount(1);
    expect($result['sponsors']->first()->size)->toBe('large');
});

it('returns event faqs and global faqs merged and sorted', function (): void {
    $event = DonationEvent::factory()->create();

    $globalFaq = Faq::factory()->create(['title' => 'Global FAQ']);
    $eventFaq = Faq::factory()->create(['title' => 'Event FAQ']);
    $unpublishedFaq = Faq::factory()->create(['title' => 'Unpublished']);
    $otherEventFaq = Faq::factory()->create(['title' => 'Other Event']);

    $event->faqs()->attach($eventFaq->id, ['group' => 'athletes', 'sort_order' => 10, 'is_published' => true]);
    $event->faqs()->attach($unpublishedFaq->id, ['group' => 'general', 'sort_order' => 1, 'is_published' => false]);

    $otherEvent = DonationEvent::factory()->create();
    $otherEvent->faqs()->attach($otherEventFaq->id, ['group' => 'general', 'sort_order' => 1, 'is_published' => true]);

    $action = new GetCurrentEventPublicDataAction;
    $result = $action($event);

    expect($result['faqs'])->toHaveCount(2);

    $titles = $result['faqs']->pluck('title')->all();
    expect($titles)->toContain('Event FAQ');
    expect($titles)->toContain('Global FAQ');

    $eventFaqResult = $result['faqs']->firstWhere('title', 'Event FAQ');
    expect($eventFaqResult->group_name)->toBe('athletes');
    expect($eventFaqResult->group_sort_order)->toBe(10);

    $globalFaqResult = $result['faqs']->firstWhere('title', 'Global FAQ');
    expect($globalFaqResult->group_name)->toBe('general');
    expect($globalFaqResult->group_sort_order)->toBe(9999);
});

it('sorts faqs by group name, sort order and title', function (): void {
    $event = DonationEvent::factory()->create();

    $faqA = Faq::factory()->create(['title' => 'Alpha']);
    $faqB = Faq::factory()->create(['title' => 'Beta']);

    $event->faqs()->attach($faqA->id, ['group' => 'general', 'sort_order' => 20, 'is_published' => true]);
    $event->faqs()->attach($faqB->id, ['group' => 'general', 'sort_order' => 10, 'is_published' => true]);

    $action = new GetCurrentEventPublicDataAction;
    $result = $action($event);

    expect($result['faqs']->pluck('title')->all())->toBe(['Beta', 'Alpha']);
});

it('returns only global faqs when no event is given', function (): void {
    $globalFaq = Faq::factory()->create(['title' => 'Global']);
    $event = DonationEvent::factory()->create();
    $eventFaq = Faq::factory()->create(['title' => 'Event']);
    $event->faqs()->attach($eventFaq->id, ['group' => 'general', 'sort_order' => 1, 'is_published' => true]);

    $action = new GetCurrentEventPublicDataAction;
    $result = $action(null);

    expect($result['faqs'])->toHaveCount(1);
    expect($result['faqs']->first()->title)->toBe('Global');
    expect($result['faqs']->first()->group_name)->toBe('general');
    expect($result['faqs']->first()->group_sort_order)->toBe(9999);
});

it('memoizes results per request', function (): void {
    $event = DonationEvent::factory()->create();
    $partner = Partner::factory()->create();
    $event->partners()->attach($partner->id, ['sort_order' => 1, 'is_published' => true]);

    $action = new GetCurrentEventPublicDataAction;

    $first = $action($event);
    $second = $action($event);

    expect($first['partners'])->toBe($second['partners']);
});
