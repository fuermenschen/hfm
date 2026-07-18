<?php

use App\Models\Faq;
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

it('requires partner and sponsor public content', function (): void {
    $partnerColumns = collect(Schema::getColumns('partners'))->keyBy('name');
    $sponsorColumns = collect(Schema::getColumns('sponsors'))->keyBy('name');
    $sponsorPivotColumns = collect(Schema::getColumns('donation_event_sponsor'))->keyBy('name');

    expect($partnerColumns['logo_light_filename']['nullable'])->toBeFalse()
        ->and($partnerColumns['logo_dark_filename']['nullable'])->toBeFalse()
        ->and($partnerColumns['beneficiary_blurb']['nullable'])->toBeFalse()
        ->and($partnerColumns['url']['nullable'])->toBeFalse()
        ->and($sponsorColumns['description']['nullable'])->toBeFalse()
        ->and($sponsorColumns['url']['nullable'])->toBeFalse()
        ->and($sponsorPivotColumns['contribution_text']['nullable'])->toBeFalse();
});
