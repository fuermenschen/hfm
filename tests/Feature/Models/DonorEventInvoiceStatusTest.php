<?php

use App\Enums\DonorInvoiceStatus;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Services\DonorInvoiceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;

function statusOf(DonorEventInvoice $invoice): DonorInvoiceStatus
{
    return app(DonorInvoiceService::class)->status($invoice);
}

function overdueOf(DonorEventInvoice $invoice): bool
{
    return app(DonorInvoiceService::class)->isOverdue($invoice);
}

it('derives NotCreated for a fresh invoice row', function (): void {
    $invoice = DonorEventInvoice::factory()->create();

    expect(statusOf($invoice))->toBe(DonorInvoiceStatus::NotCreated)
        ->and(overdueOf($invoice))->toBeFalse();
});

it('derives Created for an invoice with a debitor', function (): void {
    $invoice = DonorEventInvoice::factory()->create(['webling_debitor_id' => 42]);

    expect(statusOf($invoice))->toBe(DonorInvoiceStatus::Created);
});

it('derives Sent for a sent invoice in open state', function (): void {
    $invoice = DonorEventInvoice::factory()->create([
        'webling_debitor_id' => 42,
        'webling_state' => 'open',
        'invoice_sent_at' => now(),
    ]);

    expect(statusOf($invoice))->toBe(DonorInvoiceStatus::Sent);
});

it('derives PartiallyPaid for a not yet due partially paid invoice', function (): void {
    $invoice = DonorEventInvoice::factory()->create([
        'webling_debitor_id' => 42,
        'webling_state' => 'partially paid',
        'webling_due_date' => today()->addWeek(),
    ]);
    $invoice->load('donationEvent');

    expect(statusOf($invoice))->toBe(DonorInvoiceStatus::PartiallyPaid)
        ->and(overdueOf($invoice))->toBeFalse();
});

it('derives Overdue for open and partially paid invoices past due', function (): void {
    $open = DonorEventInvoice::factory()->create([
        'webling_debitor_id' => 42,
        'webling_state' => 'open',
        'webling_due_date' => today()->subWeek(),
        'invoice_sent_at' => now(),
    ]);
    $partial = DonorEventInvoice::factory()->create([
        'webling_debitor_id' => 43,
        'webling_state' => 'partially paid',
        'webling_due_date' => today()->subDay(),
    ]);
    $open->load('donationEvent');
    $partial->load('donationEvent');

    expect(statusOf($open))->toBe(DonorInvoiceStatus::Overdue)
        ->and(overdueOf($open))->toBeTrue()
        ->and(statusOf($partial))->toBe(DonorInvoiceStatus::Overdue)
        ->and(overdueOf($partial))->toBeTrue();
});

it('derives Paid and Writeoff regardless of other fields', function (): void {
    $paid = DonorEventInvoice::factory()->create([
        'webling_debitor_id' => 42,
        'webling_state' => 'paid',
        'webling_due_date' => today()->subWeek(),
        'invoice_sent_at' => now(),
    ]);
    $writeoff = DonorEventInvoice::factory()->create([
        'webling_debitor_id' => 43,
        'webling_state' => 'writeoff',
        'webling_due_date' => today()->subWeek(),
    ]);

    expect(statusOf($paid))->toBe(DonorInvoiceStatus::Paid)
        ->and(statusOf($writeoff))->toBe(DonorInvoiceStatus::Writeoff)
        ->and(overdueOf($paid))->toBeFalse();
});

it('derives RemoteDeleted with the highest precedence', function (): void {
    $invoice = DonorEventInvoice::factory()->create([
        'webling_debitor_id' => null,
        'webling_state' => 'paid',
        'remote_deleted_at' => now(),
    ]);

    expect(statusOf($invoice))->toBe(DonorInvoiceStatus::RemoteDeleted);
});

it('derives Unknown for unrecognized Webling states', function (): void {
    $invoice = DonorEventInvoice::factory()->create([
        'webling_debitor_id' => 42,
        'webling_state' => 'shredded',
    ]);

    expect(statusOf($invoice))->toBe(DonorInvoiceStatus::Unknown)
        ->and(overdueOf($invoice))->toBeFalse();
});

it('treats a due date of today as not overdue', function (): void {
    $invoice = DonorEventInvoice::factory()->create([
        'webling_debitor_id' => 42,
        'webling_state' => 'open',
        'webling_due_date' => today(),
    ]);
    $invoice->load('donationEvent');

    expect(overdueOf($invoice))->toBeFalse()
        ->and(statusOf($invoice))->toBe(DonorInvoiceStatus::Created);
});

it('uses the invoice event timezone to determine whether a date is overdue', function (): void {
    Date::setTestNow(Carbon::parse('2026-01-01 00:30:00', 'UTC'));

    try {
        $event = DonationEvent::factory()->create(['timezone' => 'America/Los_Angeles']);
        $invoice = DonorEventInvoice::factory()->forEvent($event)->create([
            'webling_debitor_id' => 42,
            'webling_state' => 'open',
            'webling_due_date' => '2025-12-31',
        ]);
        $invoice->setRelation('donationEvent', $event);

        expect(overdueOf($invoice))->toBeFalse();
    } finally {
        Date::setTestNow();
    }
});

it('deletes the pdf and clears lifecycle fields when marked remotely deleted', function (): void {
    Storage::fake('local');
    $path = 'webling/donor-invoices/1/test.pdf';
    Storage::disk('local')->put($path, '%PDF-test');
    $invoice = DonorEventInvoice::factory()->create([
        'webling_debitor_id' => 42,
        'webling_invoice_number' => '1542',
        'webling_state' => 'open',
        'webling_due_date' => today()->addWeek(),
        'webling_total_cents' => 1000,
        'webling_remaining_cents' => 1000,
        'webling_synced_at' => now(),
        'pdf_disk' => 'local',
        'pdf_path' => $path,
        'invoice_sent_at' => now(),
        'invoice_reminder_sent_at' => now(),
        'source_snapshot' => ['total_cents' => 1000],
        'source_total_cents' => 1000,
    ]);

    $invoice->markRemotelyDeleted();

    expect($invoice->refresh()->remote_deleted_at)->not->toBeNull()
        ->and($invoice->webling_debitor_id)->toBeNull()
        ->and($invoice->webling_invoice_number)->toBeNull()
        ->and($invoice->webling_state)->toBeNull()
        ->and($invoice->webling_due_date)->toBeNull()
        ->and($invoice->webling_total_cents)->toBeNull()
        ->and($invoice->webling_remaining_cents)->toBeNull()
        ->and($invoice->webling_synced_at)->toBeNull()
        ->and($invoice->pdf_disk)->toBeNull()
        ->and($invoice->pdf_path)->toBeNull()
        ->and($invoice->invoice_sent_at)->toBeNull()
        ->and($invoice->invoice_reminder_sent_at)->toBeNull()
        ->and($invoice->source_snapshot)->toBeNull()
        ->and($invoice->source_total_cents)->toBeNull()
        ->and(statusOf($invoice))->toBe(DonorInvoiceStatus::RemoteDeleted);
    Storage::disk('local')->assertMissing($path);
});
