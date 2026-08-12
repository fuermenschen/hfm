<?php

use App\Components\AdminDonationEventTable;
use App\Models\DonationEvent;
use Database\Seeders\DonationEventSeeder;
use Livewire\Livewire;

use function Pest\Laravel\seed;

it('seeds canonical donation events idempotently', function (): void {
    seed(DonationEventSeeder::class);
    seed(DonationEventSeeder::class);

    expect(DonationEvent::query()->count())->toBe(2)
        ->and(DonationEvent::query()->orderBy('slug')->pluck('slug')->all())->toBe(['2025', '2026'])
        ->and(DonationEvent::query()->where('slug', '2026')->value('location_url'))->toBe('https://s.geo.admin.ch/yat5fpx761jk')
        ->and((bool) DonationEvent::query()->where('slug', '2026')->value('is_published'))->toBeTrue()
        ->and(data_get(DonationEvent::query()->where('slug', '2026')->firstOrFail()->content, 'hero.copy_md'))->not->toBeNull()
        ->and(data_get(DonationEvent::query()->where('slug', '2025')->firstOrFail()->content, 'faq.general_event_md'))->toBeNull()
        ->and(data_get(DonationEvent::query()->where('slug', '2026')->firstOrFail()->content, 'faq.general_event_md'))->toBeNull();
});

it('renders donation events in the admin donation event datatable', function (): void {
    DonationEvent::query()->create([
        'slug' => '2028',
        'title' => 'Höhenmeter für Menschen',
        'timezone' => 'Europe/Zurich',
        'starts_at' => '2028-09-09 13:00:00',
        'ends_at' => '2028-09-09 18:00:00',
        'registration_opens_at' => '2028-02-01 00:00:00',
        'athlete_registration_closes_at' => '2028-09-09 16:00:00',
        'donor_registration_closes_at' => '2028-09-20 23:59:59',
        'location_name' => 'Brühlgut Stiftung',
        'location_street' => 'Brühlbergstrasse 6',
        'location_postal_code' => '8400',
        'location_city' => 'Winterthur',
        'location_url' => 'https://s.geo.admin.ch/yat5fpx761jk',
    ]);

    Livewire::test(AdminDonationEventTable::class)
        ->assertSee('2028')
        ->assertSee('Höhenmeter für Menschen');
});

it('sorts donation events newest first by default', function (): void {
    $olderEvent = DonationEvent::factory()->create([
        'slug' => '2025',
        'title' => 'Älterer Anlass',
        'starts_at' => '2025-09-09 13:00:00',
    ]);
    $newerEvent = DonationEvent::factory()->create([
        'slug' => '2026',
        'title' => 'Neuerer Anlass',
        'starts_at' => '2026-09-09 13:00:00',
    ]);

    Livewire::test(AdminDonationEventTable::class)
        ->assertSet('sortField', 'starts_at')
        ->assertSet('sortDirection', 'desc')
        ->assertSeeInOrder([$newerEvent->slug, $olderEvent->slug]);
});
