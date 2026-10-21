<?php

use App\Enums\DonorPaymentVerification;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\Partner;
use App\Services\InvoicePaymentSummaryService;

use function Pest\Laravel\artisan;
use function Pest\Laravel\travel;

it('backfills only unambiguous event partner IDs and equal-split metadata', function (): void {
    $event = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();
    $eventPartner = Partner::factory()->create(['name' => 'Event partner']);
    $otherEventPartner = Partner::factory()->create(['name' => 'Other event partner']);
    $event->partners()->attach($eventPartner->id);
    $otherEvent->partners()->attach($otherEventPartner->id);
    $invoice = DonorEventInvoice::factory()->forEvent($event)->create([
        'source_snapshot' => [
            'total_cents' => 17000,
            'lines' => [
                ['partner' => 'Event partner', 'total_cents' => 5000],
                ['partner' => __('app.equal_split'), 'total_cents' => 7000],
                ['partner' => 'Other event partner', 'total_cents' => 3000],
                ['partner_id' => $eventPartner->id, 'partner' => __('app.equal_split'), 'total_cents' => 2000],
            ],
        ],
    ]);

    artisan('invoices:backfill-partner-ids')
        ->expectsOutputToContain('Invoices updated: 1')
        ->expectsOutputToContain('Partner IDs resolved: 1')
        ->expectsOutputToContain('Equal-split lines normalized: 1')
        ->expectsOutputToContain('Already structured lines: 1')
        ->expectsOutputToContain('Unresolved legacy lines: 1')
        ->assertSuccessful();

    $snapshot = $invoice->fresh()->source_snapshot;
    expect($snapshot['lines'][0]['partner_id'])->toBe($eventPartner->id)
        ->and($snapshot['lines'][1]['partner_id'])->toBeNull()
        ->and($snapshot['lines'][2])->not->toHaveKey('partner_id')
        ->and($snapshot['lines'][3]['partner_id'])->toBe($eventPartner->id)
        ->and($snapshot['allocation_partner_ids'])->toBe([$eventPartner->id])
        ->and($snapshot['total_cents'])->toBe(17000);

    $updatedAt = $invoice->fresh()->updated_at;
    travel(5)->seconds();
    artisan('invoices:backfill-partner-ids')
        ->expectsOutputToContain('Invoices updated: 0')
        ->expectsOutputToContain('Unresolved legacy lines: 1')
        ->assertSuccessful();

    expect($invoice->fresh()->updated_at->toDateTimeString())->toBe($updatedAt->toDateTimeString());
});

it('distinguishes explicit legacy null from missing or empty partner data', function (array $line, bool $equalSplit): void {
    $event = DonationEvent::factory()->create();
    $partner = Partner::factory()->create();
    $event->partners()->attach($partner);
    $invoice = DonorEventInvoice::factory()->forEvent($event)->create([
        'source_snapshot' => ['lines' => [$line + ['total_cents' => 10000]]],
    ]);

    artisan('invoices:backfill-partner-ids')
        ->expectsOutputToContain('Equal-split lines normalized: '.($equalSplit ? 1 : 0))
        ->expectsOutputToContain('Unresolved legacy lines: '.($equalSplit ? 0 : 1))
        ->assertSuccessful();

    $snapshot = $invoice->fresh()->source_snapshot;
    if ($equalSplit) {
        expect($snapshot['lines'][0])->toMatchArray(['partner_id' => null, 'partner' => __('app.equal_split'), 'total_cents' => 10000])
            ->and($snapshot['allocation_partner_ids'])->toBe([$partner->id]);
    } else {
        expect($snapshot)->toBe(['lines' => [$line + ['total_cents' => 10000]]]);
    }

    artisan('invoices:backfill-partner-ids')->expectsOutputToContain('Invoices updated: 0')->assertSuccessful();
})->with([
    'explicit legacy null is equal split' => [['partner' => null], true],
    'missing key stays unresolved' => [[], false],
    'empty name stays unresolved' => [['partner' => ''], false],
    'invalid structured ID takes precedence' => [['partner_id' => 'invalid', 'partner' => null], false],
]);

it('preserves explicit partner IDs when legacy display name is null', function (): void {
    $event = DonationEvent::factory()->create();
    $partner = Partner::factory()->create();
    $event->partners()->attach($partner);
    $snapshot = ['lines' => [['partner_id' => $partner->id, 'partner' => null, 'total_cents' => 10000]]];
    $invoice = DonorEventInvoice::factory()->forEvent($event)->create(['source_snapshot' => $snapshot]);

    artisan('invoices:backfill-partner-ids')->expectsOutputToContain('Invoices updated: 0')->assertSuccessful();

    expect($invoice->fresh()->source_snapshot)->toBe($snapshot);
});

it('resolves seven historical equal-split lines including proportional excess without changing cash or totals', function (): void {
    $event = DonationEvent::factory()->create();
    $a = Partner::factory()->create(['name' => 'Brühlgut Stiftung']);
    $b = Partner::factory()->create(['name' => 'Vereinigung Begleitung Kranker']);
    $c = Partner::factory()->create(['name' => 'Stiftung Windlicht']);
    $event->partners()->attach([$a->id, $b->id, $c->id]);
    $facts = [
        'webling_state' => 'paid', 'webling_remaining_cents' => 0,
        'webling_payment_verification' => DonorPaymentVerification::Verified,
        'webling_payment_date' => '2026-10-12', 'webling_synced_at' => now(),
    ];
    $first = DonorEventInvoice::factory()->forEvent($event)->create($facts + [
        'webling_debitor_id' => 3720, 'webling_received_cents' => 56000, 'source_total_cents' => 56000,
        'source_snapshot' => ['total_cents' => 56000, 'lines' => [
            ['partner' => $b->name, 'total_cents' => 11000],
            ['partner' => $c->name, 'total_cents' => 10000],
            ['partner' => $c->name, 'total_cents' => 25000],
            ['partner' => null, 'total_cents' => 10000],
        ]],
    ]);
    $second = DonorEventInvoice::factory()->forEvent($event)->create($facts + [
        'webling_debitor_id' => 4010, 'webling_received_cents' => 55000, 'source_total_cents' => 54500,
        'source_snapshot' => ['total_cents' => 54500, 'lines' => [
            ['partner' => null, 'total_cents' => 10000],
            ['partner' => $c->name, 'total_cents' => 5000],
            ['partner' => null, 'total_cents' => 5000],
            ['partner' => null, 'total_cents' => 7500],
            ['partner' => null, 'total_cents' => 7500],
            ['partner' => null, 'total_cents' => 6000],
            ['partner' => null, 'total_cents' => 6000],
            ['partner' => $a->name, 'total_cents' => 7500],
        ]],
    ]);
    $summary = app(InvoicePaymentSummaryService::class);
    expect($summary->summarize($event, collect())['unallocated_cents'])->toBe(52385);

    artisan('invoices:backfill-partner-ids')
        ->expectsOutputToContain('Invoices updated: 2')
        ->expectsOutputToContain('Equal-split lines normalized: 7')
        ->expectsOutputToContain('Unresolved legacy lines: 0')->assertSuccessful();

    $result = $summary->summarize($event, collect());
    expect($result['unallocated_cents'])->toBe(0)
        ->and($result['received_cents'])->toBe(111000)
        ->and(array_sum(array_column($result['partners'], 'amount_cents')))->toBe(111000);
    foreach ([$first, $second] as $invoice) {
        $stored = $invoice->fresh();
        expect($stored->source_total_cents)->toBe($invoice->source_total_cents)
            ->and($stored->source_snapshot['total_cents'])->toBe($invoice->source_total_cents)
            ->and($stored->webling_received_cents)->toBe($invoice->webling_received_cents)
            ->and($stored->source_snapshot['allocation_partner_ids'])->toBe([$a->id, $b->id, $c->id]);
    }

    artisan('invoices:backfill-partner-ids')->expectsOutputToContain('Invoices updated: 0')->assertSuccessful();
});
