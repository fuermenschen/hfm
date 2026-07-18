<?php

use App\Models\Faq;
use App\Models\Partner;
use App\Models\Sponsor;
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
