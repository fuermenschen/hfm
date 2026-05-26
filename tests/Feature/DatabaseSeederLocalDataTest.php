<?php

declare(strict_types=1);

use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Services\CurrentDonationEventService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Cache;
use function Pest\Laravel\seed;

it('seeds local development graph with external users and two events', function (): void {
    config()->set('app.env', 'local');

    seed(DatabaseSeeder::class);

    expect(DonationEvent::query()->whereIn('slug', ['2025', '2026'])->count())->toBe(2)
        ->and(Partner::query()->count())->toBe(6)
        ->and(ExternalUser::query()->count())->toBe(100)
        ->and(AthleteRegistration::query()->count())->toBe(33)
        ->and(Donation::query()->count())->toBe(220)
        ->and(Donation::query()->whereNull('donor_external_user_id')->count())->toBe(0)
        ->and(Donation::query()->whereNull('athlete_registration_id')->count())->toBe(0)
        ->and(Donation::query()->whereRelation('athleteRegistration.donationEvent', 'slug', '2025')->count())->toBe(70)
        ->and(Donation::query()->whereRelation('athleteRegistration.donationEvent', 'slug', '2026')->count())->toBe(150)
        ->and(AthleteRegistration::query()->whereRelation('donationEvent', 'slug', '2025')->count())->toBe(10)
        ->and(AthleteRegistration::query()->whereRelation('donationEvent', 'slug', '2026')->count())->toBe(23);

    Cache::flush();

    $currentEvent = resolve(CurrentDonationEventService::class)->current();

    expect($currentEvent)->not->toBeNull()
        ->and($currentEvent?->slug)->toBe('2026');
});
