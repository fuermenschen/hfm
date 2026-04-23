<?php

use App\Models\DonationEvent;
use App\Models\Faq;
use App\Models\Partner;
use App\Models\Sponsor;
use Database\Seeders\DonationEventSeeder;
use Database\Seeders\EventContentBackfillSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('stores sponsors and faqs as global catalog entities', function (): void {
    Sponsor::factory()->create();
    Faq::factory()->create();

    expect(Schema::hasColumn('sponsors', 'donation_event_id'))->toBeFalse()
        ->and(Schema::hasColumn('faqs', 'donation_event_id'))->toBeFalse()
        ->and(Sponsor::query()->count())->toBe(1)
        ->and(Faq::query()->count())->toBe(1);
});

it('supports partner logo rendering based on available logo paths', function (): void {
    $withoutLogo = Partner::query()->create([
        'name' => 'Partner Ohne Logo',
        'logo_light_filename' => null,
        'logo_dark_filename' => null,
    ]);

    $regular = Partner::query()->create([
        'name' => 'Bruehlgut Stiftung',
        'logo_light_filename' => 'bruehlgut_light.svg',
        'logo_dark_filename' => 'bruehlgut_dark.svg',
    ]);

    expect($withoutLogo->shouldDisplayLogo())->toBeFalse()
        ->and($regular->shouldDisplayLogo())->toBeTrue();
});

it('adds expected columns for partner, sponsor, and faq models', function (): void {
    expect(Schema::hasColumns('partners', [
        'logo_light_filename',
        'logo_dark_filename',
        'beneficiary_blurb',
        'url',
    ]))->toBeTrue()
        ->and(Schema::hasColumns('sponsors', [
            'name',
            'description',
            'logo_filename',
            'url',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('faqs', [
            'title',
            'content_md',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('donation_event_partner', [
            'donation_event_id',
            'partner_id',
            'sort_order',
            'is_published',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('donation_event_sponsor', [
            'donation_event_id',
            'sponsor_id',
            'size',
            'contribution_text',
            'sort_order',
            'is_published',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('donation_event_faq', [
            'donation_event_id',
            'faq_id',
            'group',
            'sort_order',
            'is_published',
        ]))->toBeTrue()
        ->and(Schema::hasColumns('donation_event_sport_type', [
            'donation_event_id',
            'sport_type_id',
            'sort_order',
            'is_enabled',
        ]))->toBeTrue();
});

it('seeds canonical partners and sponsors for 2025 and 2026 through backfill seeder', function (): void {
    $this->seed(DonationEventSeeder::class);
    $this->seed(EventContentBackfillSeeder::class);

    $event2025 = DonationEvent::query()->where('slug', '2025')->firstOrFail();
    $event2026 = DonationEvent::query()->where('slug', '2026')->firstOrFail();

    expect($event2025->has_equal_split_option)->toBeTrue()
        ->and($event2026->has_equal_split_option)->toBeTrue()
        ->and(DB::table('donation_event_partner')->where('donation_event_id', $event2025->id)->where('is_published', true)->count())->toBe(3)
        ->and(DB::table('donation_event_partner')->where('donation_event_id', $event2026->id)->where('is_published', true)->count())->toBe(1)
        ->and(DB::table('donation_event_sponsor')->where('donation_event_id', $event2025->id)->where('is_published', true)->count())->toBe(4)
        ->and(DB::table('donation_event_sponsor')->where('donation_event_id', $event2026->id)->where('is_published', true)->count())->toBe(0)
        ->and(DB::table('donation_event_faq')->where('donation_event_id', $event2025->id)->count())->toBeGreaterThan(10)
        ->and(DB::table('donation_event_faq')->where('donation_event_id', $event2026->id)->count())->toBeGreaterThan(10)
        ->and(DB::table('faqs')->where('content_md', 'like', '%<iframe%')->count())->toBe(0);
});

it('keeps faq backfill idempotent when existing faq content changes', function (): void {
    $this->seed(DonationEventSeeder::class);
    $this->seed(EventContentBackfillSeeder::class);

    $event2025 = DonationEvent::query()->where('slug', '2025')->firstOrFail();

    $timingFaqId = DB::table('donation_event_faq')
        ->where('donation_event_id', $event2025->id)
        ->where('group', 'general')
        ->where('sort_order', 10)
        ->value('faq_id');

    expect($timingFaqId)->not->toBeNull();

    DB::table('faqs')
        ->where('id', $timingFaqId)
        ->update([
            'title' => 'Mutated title',
            'content_md' => 'Mutated content',
            'updated_at' => now(),
        ]);

    $faqCountBefore = (int) DB::table('faqs')->count();
    $pivotCountBefore = (int) DB::table('donation_event_faq')->count();

    $this->seed(EventContentBackfillSeeder::class);

    expect((int) DB::table('faqs')->count())->toBe($faqCountBefore)
        ->and((int) DB::table('donation_event_faq')->count())->toBe($pivotCountBefore);

    $timingFaqContent2025 = DB::table('faqs')
        ->join('donation_event_faq', 'donation_event_faq.faq_id', '=', 'faqs.id')
        ->where('donation_event_faq.donation_event_id', $event2025->id)
        ->where('donation_event_faq.group', 'general')
        ->where('donation_event_faq.sort_order', 10)
        ->value('faqs.content_md');

    expect((string) $timingFaqContent2025)->toContain('13 Uhr bis 18 Uhr');
});
