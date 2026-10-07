<?php

use App\Models\DonationEvent;
use App\Settings\EventSettings;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\get;
use function Pest\Laravel\travelTo;

test('renders public menu without a Livewire component', function (): void {
    get(route('home'))
        ->assertSee('Startseite')
        ->assertDontSee('menuItems')
        ->assertDontSee('footerItems');
});

test('shows registration links only while each role window is open', function (bool $athleteOpen, bool $donorOpen): void {
    travelTo(Date::parse('2026-09-12 14:00:00', 'Europe/Zurich'));
    $event = DonationEvent::factory()->create([
        'registration_opens_at' => '2026-09-01 00:00:00',
        'athlete_registration_closes_at' => $athleteOpen ? '2026-09-13 00:00:00' : '2026-09-11 00:00:00',
        'donor_registration_closes_at' => $donorOpen ? '2026-09-13 00:00:00' : '2026-09-11 00:00:00',
    ]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    $html = get(route('home'))->getContent();

    expect(str_contains($html, 'href="'.route('become-athlete').'"'))->toBe($athleteOpen);
    expect(str_contains($html, 'href="'.route('become-donor').'"'))->toBe($donorOpen);
})->with([
    'both windows open' => [true, true],
    'athlete window only' => [true, false],
    'donor window only' => [false, true],
    'neither window open' => [false, false],
]);

test('marks the FAQ item active on an edition-specific FAQ page', function (): void {
    travelTo(Date::parse('2026-09-12 14:00:00', 'Europe/Zurich'));
    $event = DonationEvent::factory()->create([
        'slug' => 'past',
        'starts_at' => '2026-09-11 12:00:00',
        'ends_at' => '2026-09-11 16:00:00',
        'registration_opens_at' => '2026-09-01 00:00:00',
        'athlete_registration_closes_at' => '2026-09-11 16:00:00',
        'donor_registration_closes_at' => '2026-09-11 16:00:00',
    ]);
    $settings = app(EventSettings::class);
    $settings->current_event_id = $event->id;
    $settings->save();

    get(route('questions-and-answers.show', ['donationEvent' => $event->slug]))
        ->assertSee('text-hfm-red dark:text-hfm-lightred font-medium');
});
