<?php

use App\Actions\RefreshDonorInvoiceStatusAction;
use App\Enums\DonorInvoiceStatus;
use App\Exceptions\DonorInvoiceGuardException;
use App\Exceptions\Webling\WeblingApiException;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use App\Services\DonorInvoiceService;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Storage::fake('local');
});

function refreshInvoiceFixture(array $overrides = []): DonorEventInvoice
{
    $invoice = DonorEventInvoice::factory()->create($overrides + [
        'webling_debitor_id' => 4321,
        'source_total_cents' => 1500,
        'pdf_disk' => 'local',
        'pdf_path' => 'webling/donor-invoices/'.Str::uuid().'/test.pdf',
    ]);
    Storage::disk('local')->put($invoice->pdf_path, '%PDF-test');

    return $invoice;
}

function refreshWeblingMock(array $details, int $times = 1): WeblingInvoiceService
{
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('invoiceDetails')->times($times)->andReturn($details);

    return $webling;
}

it('updates raw state, amounts, invoice number, and sync time from webling', function (): void {
    $invoice = refreshInvoiceFixture();
    $webling = refreshWeblingMock([
        'state' => 'partially paid',
        'due_date' => '2099-01-31',
        'invoice_number' => '1542',
        'total_cents' => 1500,
        'remaining_cents' => 500,
    ]);

    app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice);

    $invoice->refresh()->load('donationEvent');
    expect($invoice->webling_state)->toBe('partially paid')
        ->and($invoice->webling_due_date->toDateString())->toBe('2099-01-31')
        ->and($invoice->webling_invoice_number)->toBe('1542')
        ->and($invoice->webling_total_cents)->toBe(1500)
        ->and($invoice->webling_remaining_cents)->toBe(500)
        ->and($invoice->webling_synced_at)->not->toBeNull()
        ->and(app(DonorInvoiceService::class)->status($invoice))->toBe(DonorInvoiceStatus::PartiallyPaid);
});

it('marks the invoice remotely deleted on a confirmed 404', function (): void {
    $invoice = refreshInvoiceFixture();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('invoiceDetails')->once()->andThrow(
        new WeblingApiException(new Response(new GuzzleHttp\Psr7\Response(404)), WeblingApiException::NotFound),
    );

    app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice);

    $invoice->refresh();
    expect($invoice->remote_deleted_at)->not->toBeNull()
        ->and($invoice->webling_debitor_id)->toBeNull()
        ->and($invoice->pdf_path)->toBeNull()
        ->and(app(DonorInvoiceService::class)->status($invoice))->toBe(DonorInvoiceStatus::RemoteDeleted);
    Storage::disk('local')->assertMissing($invoice->getOriginal('pdf_path'));
});

it('preserves cached values and reports failure for other webling errors', function (): void {
    $invoice = refreshInvoiceFixture();
    $invoice->forceFill(['webling_state' => 'open'])->save();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('invoiceDetails')->once()->andThrow(
        new WeblingApiException(new Response(new GuzzleHttp\Psr7\Response(500)), WeblingApiException::Transient),
    );

    expect(fn () => app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(WeblingApiException::class);

    $invoice->refresh();
    expect($invoice->webling_state)->toBe('open')
        ->and($invoice->webling_synced_at)->toBeNull()
        ->and($invoice->pdf_path)->not->toBeNull();
});

it('skips invoices that are not created in webling', function (): void {
    $invoice = refreshInvoiceFixture(['webling_debitor_id' => null]);
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldNotReceive('invoiceDetails');

    expect(fn () => app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'nicht erstellt');
});

it('skips remotely deleted invoices', function (): void {
    $invoice = refreshInvoiceFixture();
    $invoice->forceFill(['remote_deleted_at' => now(), 'webling_debitor_id' => null, 'pdf_disk' => null, 'pdf_path' => null])->save();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldNotReceive('invoiceDetails');

    expect(fn () => app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'gelöscht');
});

it('refreshes only the given invoice', function (): void {
    $event = DonationEvent::factory()->create();
    $other = DonorEventInvoice::factory()->forEvent($event)->create(['webling_debitor_id' => 9999]);
    $invoice = refreshInvoiceFixture();
    $webling = refreshWeblingMock(['state' => 'paid', 'due_date' => null, 'invoice_number' => '77', 'total_cents' => 1500, 'remaining_cents' => 0]);

    app(RefreshDonorInvoiceStatusAction::class, ['weblingInvoices' => $webling])($invoice);

    expect($other->refresh()->webling_state)->toBeNull()
        ->and($other->webling_synced_at)->toBeNull();
});
