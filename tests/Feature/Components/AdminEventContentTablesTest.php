<?php

use App\Components\AdminFaqTable;
use App\Components\AdminPartnerTable;
use App\Components\AdminSponsorTable;
use App\Models\DonationEvent;
use App\Models\Faq;
use App\Models\Partner;
use App\Models\Sponsor;
use App\Support\Datatable\DatatableValueFormatter;
use Livewire\Livewire;

it('renders partners in admin table and includes donation event assignment count', function (): void {
    $events = DonationEvent::factory()->count(2)->create();

    $assignedPartner = Partner::query()->create([
        'name' => 'Bruehlgut Stiftung',
        'logo_light_filename' => 'bruehlgut_light.svg',
        'logo_dark_filename' => 'bruehlgut_dark.svg',
    ]);

    $unassignedPartner = Partner::query()->create([
        'name' => 'Unassigned Partner',
    ]);

    $assignedPartner->donationEvents()->attach($events[0]->id, ['sort_order' => 1, 'is_published' => true]);
    $assignedPartner->donationEvents()->attach($events[1]->id, ['sort_order' => 1, 'is_published' => true]);

    Livewire::test(AdminPartnerTable::class)
        ->assertSee('Bruehlgut Stiftung')
        ->tap(function ($component) use ($assignedPartner, $unassignedPartner): void {
            $rows = $component->viewData('partners')->getCollection();

            expect($rows->firstWhere('id', $assignedPartner->id)?->donation_events_count)->toBe(2);
            expect($rows->firstWhere('id', $unassignedPartner->id)?->donation_events_count)->toBe(0);
        });
});

it('renders sponsors in admin table and includes donation event assignment count', function (): void {
    $event = DonationEvent::factory()->create();

    $assignedSponsor = Sponsor::query()->create([
        'name' => 'Rohner Spiller',
        'description' => 'Druckpartner',
        'logo_filename' => 'rohner_spiller.svg',
        'url' => 'https://example.test/rohner',
    ]);

    $unassignedSponsor = Sponsor::query()->create([
        'name' => 'Unassigned Sponsor',
        'description' => 'Noch ohne Anlass',
        'logo_filename' => 'unassigned.svg',
    ]);

    $assignedSponsor->donationEvents()->attach($event->id, ['sort_order' => 1, 'is_published' => true]);

    Livewire::test(AdminSponsorTable::class)
        ->assertSee('Rohner Spiller')
        ->tap(function ($component) use ($assignedSponsor, $unassignedSponsor): void {
            $rows = $component->viewData('sponsors')->getCollection();

            expect($rows->firstWhere('id', $assignedSponsor->id)?->donation_events_count)->toBe(1);
            expect($rows->firstWhere('id', $unassignedSponsor->id)?->donation_events_count)->toBe(0);
        });
});

it('renders faqs in admin table and includes donation event assignment count', function (): void {
    $events = DonationEvent::factory()->count(2)->create();

    $assignedFaq = Faq::query()->create([
        'title' => 'Wann und wo findet der Anlass statt?',
        'content_md' => 'Der Anlass findet in Winterthur statt.',
    ]);

    $unassignedFaq = Faq::query()->create([
        'title' => 'Unassigned FAQ',
        'content_md' => 'Noch keinem Anlass zugeordnet.',
    ]);

    $assignedFaq->donationEvents()->attach($events[0]->id, ['group' => 'general', 'sort_order' => 10, 'is_published' => true]);
    $assignedFaq->donationEvents()->attach($events[1]->id, ['group' => 'general', 'sort_order' => 10, 'is_published' => true]);

    Livewire::test(AdminFaqTable::class)
        ->assertSee('Wann und wo findet der Anlass statt?')
        ->tap(function ($component) use ($assignedFaq, $unassignedFaq): void {
            $rows = $component->viewData('faqs')->getCollection();

            expect($rows->firstWhere('id', $assignedFaq->id)?->donation_events_count)->toBe(2);
            expect($rows->firstWhere('id', $unassignedFaq->id)?->donation_events_count)->toBe(0);
        });
});

it('applies configured truncation for tooltip columns in event content tables', function (): void {
    $longPartnerUrl = 'https://example.test/'.str_repeat('partner-segment/', 8);
    $longSponsorUrl = 'https://example.test/'.str_repeat('sponsor-segment/', 8);
    $longFaqContent = str_repeat('Ausfuehrlicher FAQ Inhalt ', 8);

    Partner::query()->create([
        'name' => 'Long Partner',
        'url' => $longPartnerUrl,
    ]);

    Sponsor::query()->create([
        'name' => 'Long Sponsor',
        'description' => 'Beschreibung',
        'logo_filename' => 'long.svg',
        'url' => $longSponsorUrl,
    ]);

    Faq::query()->create([
        'title' => 'Long FAQ',
        'content_md' => $longFaqContent,
    ]);

    $formatter = app(DatatableValueFormatter::class);

    Livewire::test(AdminPartnerTable::class)
        ->assertSee($formatter->truncate($longPartnerUrl, 48));

    Livewire::test(AdminSponsorTable::class)
        ->assertSee($formatter->truncate($longSponsorUrl, 48));

    Livewire::test(AdminFaqTable::class)
        ->assertSee($formatter->truncate($longFaqContent, 60));
});
