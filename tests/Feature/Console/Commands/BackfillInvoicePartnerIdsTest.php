<?php

use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Models\Partner;

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
