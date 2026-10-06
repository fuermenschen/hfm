<?php

use App\Models\DonationEvent;
use App\Settings\EventSettings;

use function Pest\Laravel\get;

test('renders public menu without a Livewire component', function (): void {
    get(route('home'))
        ->assertSee('Startseite')
        ->assertDontSee('menuItems')
        ->assertDontSee('footerItems');
});

test('shows registration links when a published event is current', function (): void {
    $event = DonationEvent::factory()->create(['is_published' => true]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    get(route('home'))
        ->assertSee('Sportler:in werden')
        ->assertSee('Spender:in werden');
});
