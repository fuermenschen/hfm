<?php

use App\Models\Athlete;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\Donor;
use App\Models\Partner;
use App\Models\SportType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects legacy athlete token route to portal without auth side effects', function (): void {
    $event = DonationEvent::factory()->create();
    $partner = Partner::factory()->create();
    $sportType = SportType::query()->create(['name' => 'Laufen']);
    $athlete = Athlete::factory()->create([
        'donation_event_id' => $event->id,
        'partner_id' => $partner->id,
        'sport_type_id' => $sportType->id,
        'verified' => false,
    ]);

    $this->get(route('show-athlete', ['login_token' => $athlete->login_token]))
        ->assertRedirect(route('portal.dashboard'));

    expect((bool) $athlete->fresh()->verified)->toBeFalse();
    $this->assertGuest('external');
});

it('redirects legacy donor token routes to portal without mutation side effects', function (): void {
    $event = DonationEvent::factory()->create();
    $partner = Partner::factory()->create();
    $sportType = SportType::query()->create(['name' => 'Rad']);
    $athlete = Athlete::factory()->create([
        'donation_event_id' => $event->id,
        'partner_id' => $partner->id,
        'sport_type_id' => $sportType->id,
        'verified' => true,
    ]);
    $donor = Donor::factory()->create();

    $donation = Donation::query()->create([
        'donor_id' => $donor->id,
        'athlete_id' => $athlete->id,
        'amount_per_round' => 5,
        'amount_min' => 10,
        'amount_max' => 40,
        'verified' => false,
    ]);

    $this->get(route('show-donor', ['login_token' => $donor->login_token]))
        ->assertRedirect(route('portal.dashboard'));

    $this->get(route('verify-donation', ['login_token' => $donor->login_token, 'donation_id' => $donation->id]))
        ->assertRedirect(route('portal.dashboard'));

    expect((bool) $donation->fresh()->verified)->toBeFalse();
    $this->assertGuest('external');
});
