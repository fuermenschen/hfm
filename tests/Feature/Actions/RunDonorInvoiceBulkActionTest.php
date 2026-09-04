<?php

use App\Actions\RunDonorInvoiceBulkAction;
use App\Exceptions\DonorInvoiceGuardException;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;

it('catches unexpected errors per item and reports counts', function (): void {
    $event = DonationEvent::factory()->create();
    $invoices = DonorEventInvoice::factory()->forEvent($event)->count(4)->create();
    $runner = app(RunDonorInvoiceBulkAction::class);

    $result = $runner($event, $invoices, fn (DonorEventInvoice $invoice) => throw new RuntimeException('boom '.$invoice->id));

    expect($result['failed'])->toBe(4)
        ->and($result['successful'])->toBe(0);
});

it('counts guard rejections as skipped and unexpected errors as failed', function (): void {
    $event = DonationEvent::factory()->create();
    $invoices = DonorEventInvoice::factory()->forEvent($event)->count(3)->create();
    $runner = app(RunDonorInvoiceBulkAction::class);
    $first = $invoices[0];
    $second = $invoices[1];

    $result = $runner($event, $invoices, function (DonorEventInvoice $invoice) use ($first, $second): void {
        if ($invoice->is($first)) {
            return;
        }

        if ($invoice->is($second)) {
            throw new DonorInvoiceGuardException('Die Rechnung wurde noch nicht versendet.');
        }

        throw new RuntimeException('boom');
    });

    expect($result['successful'])->toBe(1)
        ->and($result['skipped'])->toBe(1)
        ->and($result['failed'])->toBe(1)
        ->and($result['messages'])->toHaveCount(2)
        ->and($result['messages'][0])->toContain('Rechnung '.$second->id)
        ->and($result['messages'][0])->toContain('noch nicht versendet');
});

it('does not stop after a failed item', function (): void {
    $event = DonationEvent::factory()->create();
    $invoices = DonorEventInvoice::factory()->forEvent($event)->count(3)->create();
    $runner = app(RunDonorInvoiceBulkAction::class);
    $first = $invoices[0];
    $processed = [];

    $result = $runner($event, $invoices, function (DonorEventInvoice $invoice) use ($first, &$processed): void {
        $processed[] = $invoice->id;
        if ($invoice->is($first)) {
            throw new RuntimeException('boom');
        }
    });

    expect($processed)->toHaveCount(3)
        ->and($result['failed'])->toBe(1)
        ->and($result['successful'])->toBe(2);
});

it('skips invoices that do not belong to the selected event', function (): void {
    $event = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();
    $included = DonorEventInvoice::factory()->forEvent($event)->create();
    $excluded = DonorEventInvoice::factory()->forEvent($otherEvent)->create();
    $runner = app(RunDonorInvoiceBulkAction::class);
    $processed = [];

    $result = $runner($event, [$included, $excluded], function (DonorEventInvoice $invoice) use (&$processed): void {
        $processed[] = $invoice->id;
    });

    expect($processed)->toBe([$included->id])
        ->and($result['successful'])->toBe(1)
        ->and($result['skipped'])->toBe(1)
        ->and($result['failed'])->toBe(0)
        ->and($result['messages'][0])->toContain('nicht zum ausgewählten Anlass');
});
