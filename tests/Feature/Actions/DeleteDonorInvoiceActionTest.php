<?php

use App\Actions\DeleteDonorInvoiceAction;
use App\Exceptions\DonorInvoiceGuardException;
use App\Exceptions\Webling\WeblingApiException;
use App\Models\DonorEventInvoice;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Storage::fake('local');
});

function deleteInvoiceFixture(array $overrides = []): DonorEventInvoice
{
    $invoice = DonorEventInvoice::factory()->create($overrides + [
        'webling_debitor_id' => 4321,
        'source_total_cents' => 1500,
        'invoice_sent_at' => now()->subDays(14),
        'pdf_disk' => 'local',
        'pdf_path' => 'webling/donor-invoices/'.Str::uuid().'/test.pdf',
    ]);
    Storage::disk('local')->put($invoice->pdf_path, '%PDF-test');

    return $invoice;
}

function openInvoiceDetails(): array
{
    return ['state' => 'open', 'due_date' => '2020-01-01', 'invoice_number' => '1542', 'total_cents' => 1500, 'remaining_cents' => 1500];
}

function weblingApiException(int $status, string $category): WeblingApiException
{
    return new WeblingApiException(new Response(new GuzzleHttp\Psr7\Response($status)), $category);
}

it('cleans up locally without a remote request when no debitor exists', function (): void {
    $invoice = deleteInvoiceFixture(['webling_debitor_id' => null]);
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldNotReceive('invoiceDetails');
    $webling->shouldNotReceive('deleteInvoice');

    app(DeleteDonorInvoiceAction::class, ['weblingInvoices' => $webling])($invoice);

    $invoice->refresh();
    expect($invoice->remote_deleted_at)->not->toBeNull()
        ->and($invoice->pdf_path)->toBeNull()
        ->and($invoice->source_snapshot)->toBeNull()
        ->and($invoice->invoice_sent_at)->toBeNull();
    Storage::disk('local')->assertMissing($invoice->getOriginal('pdf_path'));
});

it('deletes an open unsettled invoice in webling and locally', function (): void {
    $invoice = deleteInvoiceFixture();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('invoiceDetails')->once()->andReturn(openInvoiceDetails());
    $webling->shouldReceive('deleteInvoice')->once()->with(4321);

    app(DeleteDonorInvoiceAction::class, ['weblingInvoices' => $webling])($invoice);

    expect($invoice->refresh()->remote_deleted_at)->not->toBeNull()
        ->and($invoice->webling_debitor_id)->toBeNull();
    Storage::disk('local')->assertMissing($invoice->getOriginal('pdf_path'));
});

it('treats a confirmed 404 as successful deletion', function (): void {
    $invoice = deleteInvoiceFixture();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('invoiceDetails')->once()->andReturn(openInvoiceDetails());
    $webling->shouldReceive('deleteInvoice')->once()->andThrow(weblingApiException(404, WeblingApiException::NotFound));

    app(DeleteDonorInvoiceAction::class, ['weblingInvoices' => $webling])($invoice);

    expect($invoice->refresh()->remote_deleted_at)->not->toBeNull()
        ->and($invoice->webling_debitor_id)->toBeNull();
});

it('cleans up when the live read confirms the debitor was already deleted', function (): void {
    $invoice = deleteInvoiceFixture();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('invoiceDetails')->once()->andThrow(weblingApiException(404, WeblingApiException::NotFound));
    $webling->shouldNotReceive('deleteInvoice');

    app(DeleteDonorInvoiceAction::class, ['weblingInvoices' => $webling])($invoice);

    expect($invoice->refresh()->remote_deleted_at)->not->toBeNull()
        ->and($invoice->webling_debitor_id)->toBeNull();
    Storage::disk('local')->assertMissing($invoice->getOriginal('pdf_path'));
});

it('blocks deletion of paid and written-off invoices', function (): void {
    $invoice = deleteInvoiceFixture();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('invoiceDetails')->once()->andReturn(['state' => 'paid', 'due_date' => null, 'invoice_number' => null, 'total_cents' => 1500, 'remaining_cents' => 0]);
    $webling->shouldNotReceive('deleteInvoice');

    expect(fn () => app(DeleteDonorInvoiceAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'Bezahlte oder abgeschriebene');
    expect($invoice->refresh()->remote_deleted_at)->toBeNull()
        ->and($invoice->webling_debitor_id)->toBe(4321);
});

it('blocks deletion of partially paid and unknown invoices', function (): void {
    $invoice = deleteInvoiceFixture();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('invoiceDetails')->once()->andReturn(['state' => 'partially paid', 'due_date' => null, 'invoice_number' => null, 'total_cents' => 1500, 'remaining_cents' => 500]);
    $webling->shouldNotReceive('deleteInvoice');

    expect(fn () => app(DeleteDonorInvoiceAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'Nur offene Rechnungen');
    expect($invoice->refresh()->remote_deleted_at)->toBeNull();
});

it('fails closed when webling is unavailable before deletion', function (): void {
    $invoice = deleteInvoiceFixture();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('invoiceDetails')->once()->andThrow(weblingApiException(503, WeblingApiException::Transient));
    $webling->shouldNotReceive('deleteInvoice');

    expect(fn () => app(DeleteDonorInvoiceAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(WeblingApiException::class);
    expect($invoice->refresh()->remote_deleted_at)->toBeNull()
        ->and($invoice->webling_debitor_id)->toBe(4321);
});

it('keeps the local row untouched when the remote delete fails transiently', function (): void {
    $invoice = deleteInvoiceFixture();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldReceive('invoiceDetails')->once()->andReturn(openInvoiceDetails());
    $webling->shouldReceive('deleteInvoice')->once()->andThrow(weblingApiException(500, WeblingApiException::Transient));

    expect(fn () => app(DeleteDonorInvoiceAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(WeblingApiException::class);
    expect($invoice->refresh()->remote_deleted_at)->toBeNull()
        ->and($invoice->webling_debitor_id)->toBe(4321);
    Storage::disk('local')->assertExists($invoice->pdf_path);
});

it('skips deletion of an already remotely deleted invoice', function (): void {
    $invoice = deleteInvoiceFixture();
    $invoice->forceFill(['remote_deleted_at' => now(), 'webling_debitor_id' => null, 'pdf_disk' => null, 'pdf_path' => null])->save();
    $webling = Mockery::mock(WeblingInvoiceService::class);
    $webling->shouldNotReceive('invoiceDetails');

    expect(fn () => app(DeleteDonorInvoiceAction::class, ['weblingInvoices' => $webling])($invoice))
        ->toThrow(DonorInvoiceGuardException::class, 'bereits gelöscht');
});
