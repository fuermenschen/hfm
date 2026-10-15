<?php

use App\Enums\DonorPaymentVerification;
use App\Models\AthleteRegistration;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\ExternalUser;
use App\Models\Partner;
use App\Services\InvoicePaymentSummaryService;

function paidSummaryInvoice(DonationEvent $event, array $overrides = []): DonorEventInvoice
{
    return DonorEventInvoice::factory()->forEvent($event)->create($overrides + [
        'webling_debitor_id' => 42, 'webling_state' => 'paid',
        'webling_total_cents' => 10000, 'webling_remaining_cents' => 0,
        'webling_received_cents' => 10000, 'webling_excess_cents' => 0, 'webling_writeoff_cents' => 0,
        'webling_payment_date' => '2026-10-01', 'webling_payment_verification' => DonorPaymentVerification::Verified,
        'webling_synced_at' => now(), 'invoice_sent_at' => now(),
    ]);
}

it('allocates each invoice cash proportionally and keeps excess and writeoffs separate', function (): void {
    $event = DonationEvent::factory()->create();
    $a = Partner::factory()->create(['name' => 'A']);
    $b = Partner::factory()->create(['name' => 'B']);
    $event->partners()->attach([$a->id, $b->id]);
    paidSummaryInvoice($event, [
        'webling_received_cents' => 11000, 'webling_excess_cents' => 1000,
        'source_snapshot' => ['lines' => [
            ['partner_id' => $a->id, 'total_cents' => 9000], ['partner_id' => $b->id, 'total_cents' => 1000],
        ]],
    ]);
    paidSummaryInvoice($event, [
        'webling_received_cents' => 5000, 'webling_writeoff_cents' => 5000,
        'source_snapshot' => ['lines' => [['partner_id' => $b->id, 'total_cents' => 10000]]],
    ]);

    $summary = app(InvoicePaymentSummaryService::class)->summarize($event, collect());

    expect($summary)->toMatchArray([
        'received_cents' => 16000, 'excess_cents' => 1000, 'writeoff_cents' => 5000,
        'verified_receipts' => 2, 'sent_total' => 2, 'outstanding_cents' => 0,
        'partners' => [['name' => 'A', 'amount_cents' => 9900], ['name' => 'B', 'amount_cents' => 6100]],
    ]);
});

it('allocates indivisible cents exactly using frozen equal-split partners', function (): void {
    $event = DonationEvent::factory()->create();
    $a = Partner::factory()->create(['name' => 'A']);
    $b = Partner::factory()->create(['name' => 'B']);
    $c = Partner::factory()->create(['name' => 'C']);
    $event->partners()->attach([$a->id, $c->id]);
    paidSummaryInvoice($event, [
        'webling_received_cents' => 101,
        'source_snapshot' => ['allocation_partner_ids' => [$a->id, $b->id], 'lines' => [
            ['partner_id' => null, 'total_cents' => 100],
        ]],
    ]);

    $summary = app(InvoicePaymentSummaryService::class)->summarize($event, collect());

    expect($summary['partners'])->toBe([
        ['name' => 'A', 'amount_cents' => 51], ['name' => 'B', 'amount_cents' => 50], ['name' => 'C', 'amount_cents' => 0],
    ]);
    expect(array_sum(array_column($summary['partners'], 'amount_cents')))->toBe(101);
});

it('preserves exact weights across repeated equal-split invoice lines', function (): void {
    $event = DonationEvent::factory()->create();
    $partners = Partner::factory()->count(3)->create();
    $event->partners()->attach($partners->modelKeys());
    paidSummaryInvoice($event, [
        'webling_total_cents' => 300,
        'webling_received_cents' => 300,
        'source_snapshot' => [
            'allocation_partner_ids' => $partners->modelKeys(),
            'lines' => [
                ['partner_id' => null, 'total_cents' => 100],
                ['partner_id' => null, 'total_cents' => 100],
                ['partner_id' => null, 'total_cents' => 100],
            ],
        ],
    ]);

    $summary = app(InvoicePaymentSummaryService::class)->summarize($event, collect());

    expect(array_column($summary['partners'], 'amount_cents'))->toBe([100, 100, 100]);
});

it('preserves exact equal-split proportions when receipt includes excess payment', function (): void {
    $event = DonationEvent::factory()->create();
    $partners = Partner::factory()->count(3)->create();
    $event->partners()->attach($partners->modelKeys());
    paidSummaryInvoice($event, [
        'webling_total_cents' => 300,
        'webling_received_cents' => 900,
        'webling_excess_cents' => 600,
        'source_snapshot' => [
            'allocation_partner_ids' => $partners->modelKeys(),
            'lines' => [
                ['partner_id' => $partners[0]->id, 'total_cents' => 200],
                ['partner_id' => null, 'total_cents' => 100],
            ],
        ],
    ]);

    $summary = app(InvoicePaymentSummaryService::class)->summarize($event, collect());

    expect(array_column($summary['partners'], 'amount_cents'))->toBe([700, 100, 100]);
    expect(array_sum(array_column($summary['partners'], 'amount_cents')))->toBe(900);
});

it('resolves legacy snapshot names and equal split within each event', function (): void {
    $first = DonationEvent::factory()->create();
    $second = DonationEvent::factory()->create();
    $a = Partner::factory()->create(['name' => 'A']);
    $b = Partner::factory()->create(['name' => 'B']);
    $first->partners()->attach($a->id);
    $second->partners()->attach($b->id);
    paidSummaryInvoice($first, ['source_snapshot' => ['lines' => [['partner' => 'alle zu gleichen Teilen', 'total_cents' => 10000]]]]);
    paidSummaryInvoice($second, ['source_snapshot' => ['lines' => [['partner' => 'B', 'total_cents' => 10000]]]]);

    $service = app(InvoicePaymentSummaryService::class);
    $one = $service->summarize($first, collect());
    $all = $service->summarize(null, collect());

    expect($one['received_cents'])->toBe(10000);
    expect($one['partners'])->toBe([['name' => 'A', 'amount_cents' => 10000]]);
    expect($all['received_cents'])->toBe(20000);
    expect($all['partners'])->toBe([['name' => 'A', 'amount_cents' => 10000], ['name' => 'B', 'amount_cents' => 10000]]);
});

it('keeps cash with missing partner mappings visible instead of redistributing it', function (): void {
    $event = DonationEvent::factory()->create();
    $a = Partner::factory()->create(['name' => 'A']);
    $event->partners()->attach($a->id);
    paidSummaryInvoice($event, ['source_snapshot' => ['lines' => [
        ['partner' => 'No longer known', 'total_cents' => 2500], ['partner_id' => $a->id, 'total_cents' => 7500],
    ]]]);
    paidSummaryInvoice($event, ['webling_received_cents' => 1000, 'source_snapshot' => ['lines' => []]]);

    $summary = app(InvoicePaymentSummaryService::class)->summarize($event, collect());

    expect($summary['partners'])->toBe([['name' => 'A', 'amount_cents' => 7500]]);
    expect($summary['unallocated_cents'])->toBe(3500);
    expect($summary['received_cents'])->toBe(11000);
});

it('does not resolve a legacy partner name from another event', function (): void {
    $invoiceEvent = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();
    $renamedPartner = Partner::factory()->create(['name' => 'Renamed locally']);
    $otherEventPartner = Partner::factory()->create(['name' => 'Legacy partner name']);
    $invoiceEvent->partners()->attach($renamedPartner->id);
    $otherEvent->partners()->attach($otherEventPartner->id);
    paidSummaryInvoice($invoiceEvent, [
        'source_snapshot' => ['lines' => [['partner' => 'Legacy partner name', 'total_cents' => 10000]]],
    ]);

    $summary = app(InvoicePaymentSummaryService::class)->summarize($invoiceEvent, collect());

    expect($summary['unallocated_cents'])->toBe(10000);
    expect(array_column($summary['partners'], 'amount_cents'))->toBe([0]);
});

it('prefers stored partner IDs over stale equal-split text', function (): void {
    $event = DonationEvent::factory()->create();
    $directPartner = Partner::factory()->create(['name' => 'Direct']);
    $equalPartner = Partner::factory()->create(['name' => 'Equal']);
    $event->partners()->attach([$directPartner->id, $equalPartner->id]);
    paidSummaryInvoice($event, [
        'source_snapshot' => [
            'allocation_partner_ids' => [$directPartner->id, $equalPartner->id],
            'lines' => [[
                'partner_id' => $directPartner->id,
                'partner' => __('app.equal_split'),
                'total_cents' => 10000,
            ]],
        ],
    ]);

    $summary = app(InvoicePaymentSummaryService::class)->summarize($event, collect());

    expect(array_column($summary['partners'], 'amount_cents'))->toBe([10000, 0]);
});

it('uses current donations only when an invoice has no snapshot lines', function (): void {
    $event = DonationEvent::factory()->create();
    $a = Partner::factory()->create(['name' => 'A']);
    $b = Partner::factory()->create(['name' => 'B']);
    $event->partners()->attach([$a->id, $b->id]);
    $donor = ExternalUser::factory()->create();
    $athlete = AthleteRegistration::factory()->forEvent($event)->withPartner($a)->create(['rounds_done' => 4]);
    $donation = Donation::factory()->forPair($donor, $athlete)->create(['amount_per_round' => 5, 'amount_min' => null, 'amount_max' => null]);
    paidSummaryInvoice($event, ['external_user_id' => $donor->id, 'webling_received_cents' => 1500]);
    $donation->load(['athleteRegistration.partner']);

    $summary = app(InvoicePaymentSummaryService::class)->summarize($event, collect([$donation]));

    expect($summary['partners'])->toBe([['name' => 'A', 'amount_cents' => 1500], ['name' => 'B', 'amount_cents' => 0]]);
    expect($summary['unallocated_cents'])->toBe(0);
});

it('excludes unclear partial and deleted receipts but counts states and remaining balances', function (): void {
    $event = DonationEvent::factory()->create();
    paidSummaryInvoice($event, ['webling_payment_verification' => DonorPaymentVerification::Unclear, 'webling_excess_cents' => null]);
    paidSummaryInvoice($event, ['webling_state' => 'partially paid', 'webling_remaining_cents' => 4000, 'webling_payment_verification' => DonorPaymentVerification::Unverified, 'webling_due_date' => now()->addDay()]);
    paidSummaryInvoice($event, ['remote_deleted_at' => now()]);
    $deleted = paidSummaryInvoice($event);
    $deleted->externalUser->delete();
    DonorEventInvoice::factory()->forEvent($event)->create(['webling_debitor_id' => 45]);

    $summary = app(InvoicePaymentSummaryService::class)->summarize($event, collect());

    expect($summary)->toMatchArray(['received_cents' => 0, 'verified_receipts' => 0, 'unclear_payments' => 1, 'outstanding_cents' => 4000, 'never_synced' => 1, 'created_total' => 3, 'sent_total' => 2]);
    expect($summary['counts'])->toMatchArray(['paid' => 1, 'partially_paid' => 1, 'remote_deleted' => 1, 'created' => 1]);
});

it('does not invent excess or writeoffs for previously refreshed invoices without a breakdown', function (): void {
    $event = DonationEvent::factory()->create();
    paidSummaryInvoice($event, ['webling_excess_cents' => null, 'webling_writeoff_cents' => null]);

    $summary = app(InvoicePaymentSummaryService::class)->summarize($event, collect());

    expect($summary)->toMatchArray(['received_cents' => 10000, 'excess_cents' => 0, 'writeoff_cents' => 0, 'breakdown_missing' => 1]);
});

it('keeps stable snapshot partner IDs after names and live donation assignments change', function (): void {
    $event = DonationEvent::factory()->create();
    $a = Partner::factory()->create(['name' => 'Original partner']);
    $b = Partner::factory()->create(['name' => 'Other partner']);
    $event->partners()->attach([$a->id, $b->id]);
    $donor = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forEvent($event)->withPartner($b)->create(['rounds_done' => 100]);
    $donation = Donation::factory()->forPair($donor, $registration)->create();
    paidSummaryInvoice($event, [
        'external_user_id' => $donor->id,
        'source_snapshot' => ['lines' => [['partner_id' => $a->id, 'partner' => 'Original partner', 'total_cents' => 10000]]],
    ]);
    $a->update(['name' => 'Renamed partner']);
    $donation->load('athleteRegistration.partner');

    $summary = app(InvoicePaymentSummaryService::class)->summarize($event, collect([$donation]));

    expect($summary['partners'])->toBe([['name' => 'Renamed partner', 'amount_cents' => 10000], ['name' => 'Other partner', 'amount_cents' => 0]]);
});

it('counts missing invoices per donor and event without counting deleted donors', function (): void {
    $first = DonationEvent::factory()->create();
    $second = DonationEvent::factory()->create();
    $donor = ExternalUser::factory()->create();
    $deleted = ExternalUser::factory()->create();
    $a = AthleteRegistration::factory()->forEvent($first)->create();
    $b = AthleteRegistration::factory()->forEvent($first)->create();
    $c = AthleteRegistration::factory()->forEvent($second)->create();
    Donation::factory()->forPair($donor, $a)->create();
    Donation::factory()->forPair($donor, $b)->create();
    Donation::factory()->forPair($donor, $c)->create();
    Donation::factory()->forPair($deleted, $a)->create();
    $deleted->delete();
    $donations = Donation::query()->with('athleteRegistration.partner')->get();

    $summary = app(InvoicePaymentSummaryService::class)->summarize(null, $donations);

    expect($summary['counts']['not_created'])->toBe(2);
});
