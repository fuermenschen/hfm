<?php

use App\Components\AdminAthleteTable;
use App\Components\AdminDonationEventTable;
use App\Models\Athlete;
use App\Models\DonationEvent;
use App\Models\Partner;
use App\Models\SportType;
use Database\Seeders\DonationEventSeeder;
use Livewire\Livewire;

it('seeds canonical donation events idempotently', function (): void {
    $this->seed(DonationEventSeeder::class);
    $this->seed(DonationEventSeeder::class);

    expect(DonationEvent::query()->count())->toBe(2);
    expect(DonationEvent::query()->orderBy('slug')->pluck('slug')->all())->toBe(['2025', '2026']);
    expect(DonationEvent::query()->where('slug', '2026')->value('location_url'))->toBe('https://s.geo.admin.ch/yat5fpx761jk');
    expect((bool) DonationEvent::query()->where('slug', '2026')->value('is_published'))->toBeTrue();
    expect(data_get(DonationEvent::query()->where('slug', '2026')->firstOrFail()->content, 'hero.copy_md'))->not->toBeNull();
});

it('renders donation events in the admin donation event datatable', function (): void {
    DonationEvent::query()->create([
        'slug' => '2028',
        'title' => 'Höhenmeter für Menschen',
        'starts_at' => '2028-09-09T13:00:00.000+02:00',
        'ends_at' => '2028-09-09T18:00:00.000+02:00',
        'registration_opens_at' => '2028-02-01T00:00:00.000+01:00',
        'athlete_registration_closes_at' => '2028-09-09T16:00:00.000+02:00',
        'donor_registration_closes_at' => '2028-09-20T23:59:59.000+02:00',
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

it('shows athlete event in the admin athlete datatable', function (): void {
    $this->seed(DonationEventSeeder::class);

    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $partner = Partner::query()->create(['name' => 'Partner']);
    $donationEvent = DonationEvent::query()->where('slug', '2026')->firstOrFail();

    Athlete::factory()->create([
        'sport_type_id' => $sportType->id,
        'partner_id' => $partner->id,
        'donation_event_id' => $donationEvent->id,
    ]);

    Livewire::test(AdminAthleteTable::class)
        ->assertSee('2026');
});
