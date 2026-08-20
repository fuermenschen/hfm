<?php

declare(strict_types=1);

use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use App\Models\Faq;
use App\Models\Partner;
use App\Models\Sponsor;
use App\Models\SportType;
use App\Services\CurrentDonationEventService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\seed;

it('seeds local development graph with external users and two events', function (): void {
    config()->set('app.env', 'local');
    Storage::fake('public');
    Storage::disk('public')->put('partners/windlicht_light.svg', '<svg />');
    Storage::disk('public')->put('partners/windlicht_dark.svg', '<svg />');
    Storage::disk('public')->put('partners/vbk_light.svg', '<svg />');
    Storage::disk('public')->put('partners/vbk_dark.svg', '<svg />');

    seed(DatabaseSeeder::class);

    $soleAdminGroup = EventGroup::query()->where('name', 'Winterthur Solo')->firstOrFail();
    $multiAdminGroup = EventGroup::query()->where('name', 'Gipfelstürmerinnen')->firstOrFail();
    $pendingGroup = EventGroup::query()->where('name', 'Noch offen')->firstOrFail();

    expect(DonationEvent::query()->whereIn('slug', ['2025', '2026'])->count())->toBe(2)
        ->and(Partner::query()->count())->toBe(5)
        ->and(Sponsor::query()->count())->toBe(4)
        ->and(Faq::query()->count())->toBe(4)
        ->and(SportType::query()->count())->toBe(5)
        ->and(DonationEvent::query()->orderBy('slug')->withCount('sportTypes')->pluck('sport_types_count')->all())->toBe([5, 5])
        ->and(DonationEvent::query()->orderBy('slug')->withCount('partners')->pluck('partners_count')->all())->toBe([3, 3])
        ->and(DonationEvent::query()->orderBy('slug')->withCount('sponsors')->pluck('sponsors_count')->all())->toBe([4, 4])
        ->and(DonationEvent::query()->orderBy('slug')->withCount('faqs')->pluck('faqs_count')->all())->toBe([4, 4])
        ->and(ExternalUser::query()->count())->toBe(100)
        ->and(AthleteRegistration::query()->count())->toBe(33)
        ->and(EventGroup::query()->count())->toBe(3)
        ->and(EventGroup::query()->whereRelation('donationEvent', 'slug', '2026')->count())->toBe(3)
        ->and(AthleteRegistration::query()->where('group_membership_status', GroupMembershipStatus::Accepted)->where('group_membership_role', GroupMembershipRole::Admin)->count())->toBe(4)
        ->and(AthleteRegistration::query()->where('group_membership_status', GroupMembershipStatus::Accepted)->where('group_membership_role', GroupMembershipRole::Member)->count())->toBe(1)
        ->and(AthleteRegistration::query()->where('group_membership_status', GroupMembershipStatus::Pending)->whereNull('group_membership_role')->count())->toBe(1)
        ->and($soleAdminGroup->donation_event_id)->toBe(DonationEvent::query()->where('slug', '2026')->value('id'))
        ->and($soleAdminGroup->athleteRegistrations()->where('group_membership_status', GroupMembershipStatus::Accepted)->where('group_membership_role', GroupMembershipRole::Admin)->count())->toBe(1)
        ->and($multiAdminGroup->athleteRegistrations()->where('group_membership_status', GroupMembershipStatus::Accepted)->where('group_membership_role', GroupMembershipRole::Admin)->count())->toBe(2)
        ->and($multiAdminGroup->athleteRegistrations()->where('group_membership_status', GroupMembershipStatus::Accepted)->where('group_membership_role', GroupMembershipRole::Member)->count())->toBe(1)
        ->and($pendingGroup->athleteRegistrations()->where('group_membership_status', GroupMembershipStatus::Accepted)->where('group_membership_role', GroupMembershipRole::Admin)->count())->toBe(1)
        ->and($pendingGroup->athleteRegistrations()->where('group_membership_status', GroupMembershipStatus::Pending)->whereNull('group_membership_role')->count())->toBe(1)
        ->and(AthleteRegistration::query()->whereNull('partner_id')->count())->toBe(12)
        ->and(Donation::query()->count())->toBe(220)
        ->and(Donation::query()->whereNull('donor_external_user_id')->count())->toBe(0)
        ->and(Donation::query()->whereNull('athlete_registration_id')->count())->toBe(0)
        ->and(Donation::query()->whereRelation('athleteRegistration.donationEvent', 'slug', '2025')->count())->toBe(70)
        ->and(Donation::query()->whereRelation('athleteRegistration.donationEvent', 'slug', '2026')->count())->toBe(150)
        ->and(AthleteRegistration::query()->whereRelation('donationEvent', 'slug', '2025')->count())->toBe(10)
        ->and(AthleteRegistration::query()->whereRelation('donationEvent', 'slug', '2026')->count())->toBe(23)
        ->and(AthleteRegistration::query()->whereRelation('donationEvent', 'slug', '2025')->pluck('created_at')->unique()->count())->toBeGreaterThan(1)
        ->and(AthleteRegistration::query()->whereRelation('donationEvent', 'slug', '2026')->pluck('created_at')->unique()->count())->toBeGreaterThan(1)
        ->and(Donation::query()->whereRelation('athleteRegistration.donationEvent', 'slug', '2025')->pluck('created_at')->unique()->count())->toBeGreaterThan(1)
        ->and(Donation::query()->whereRelation('athleteRegistration.donationEvent', 'slug', '2026')->pluck('created_at')->unique()->count())->toBeGreaterThan(1);

    Cache::flush();

    $currentEvent = resolve(CurrentDonationEventService::class)->current();

    expect($currentEvent)->not->toBeNull()
        ->and($currentEvent?->slug)->toBe('2026')
        ->and($currentEvent?->partners->pluck('name')->all())->toBe([
            'Brühlgut Stiftung',
            'Vereinigung Begleitung Kranker',
            'Stiftung Windlicht',
        ])
        ->and($currentEvent?->partners()->orderByPivot('sort_order')->pluck('name')->all())->toBe([
            'Brühlgut Stiftung',
            'Vereinigung Begleitung Kranker',
            'Stiftung Windlicht',
        ])
        ->and($currentEvent?->contentValue('home.about_heading'))->toBe('Um was geht es?')
        ->and($currentEvent?->partners()->wherePivot('is_published', true)->count())->toBe(3)
        ->and($currentEvent?->sponsors()->wherePivot('is_published', true)->count())->toBe(4)
        ->and($currentEvent?->faqs()->wherePivot('is_published', true)->count())->toBe(4)
        ->and($currentEvent?->sportTypes()->wherePivot('is_enabled', true)->count())->toBe(5)
        ->and($currentEvent?->partners->every(fn (Partner $partner): bool => Storage::disk('public')->exists('partners/'.$partner->logo_light_filename)
            && Storage::disk('public')->exists('partners/'.$partner->logo_dark_filename)))->toBeTrue()
        ->and($currentEvent?->sponsors->every(fn (Sponsor $sponsor): bool => Storage::disk('public')->exists('sponsors/'.$sponsor->logo_filename)))->toBeTrue()
        ->and(AthleteRegistration::query()->with('donationEvent.partners')->get()->every(
            fn (AthleteRegistration $registration): bool => $registration->partner_id === null
                || $registration->donationEvent->partners->contains('id', $registration->partner_id),
        ))->toBeTrue();
});
