<?php

use App\Actions\DownloadDonorInvoicePdfAction;
use App\Actions\DownloadEventInvoiceArchiveAction;
use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Storage::fake('local');
});

function pdfInvoiceFixture(DonationEvent $event, array $overrides = []): DonorEventInvoice
{
    $invoice = DonorEventInvoice::factory()->forEvent($event)->create($overrides + [
        'pdf_disk' => 'local',
        'pdf_path' => 'webling/donor-invoices/'.Str::uuid().'/test.pdf',
    ]);
    Storage::disk('local')->put($invoice->pdf_path, '%PDF-'.$invoice->id);

    return $invoice;
}

it('resolves the cached pdf payload for an eligible invoice', function (): void {
    $invoice = pdfInvoiceFixture(DonationEvent::factory()->create());

    $payload = app(DownloadDonorInvoicePdfAction::class)($invoice);

    expect($payload['disk'])->toBe('local')
        ->and($payload['path'])->toBe($invoice->pdf_path)
        ->and($payload['file_name'])->toBe(sprintf('invoice_DON-%d-%d.pdf', $invoice->donation_event_id, $invoice->external_user_id));
    Storage::disk('local')->assertExists($payload['path']);
});

it('returns null when the pdf or file is missing', function (): void {
    $missingFile = pdfInvoiceFixture(DonationEvent::factory()->create());
    Storage::disk('local')->delete($missingFile->pdf_path);
    $noPdf = DonorEventInvoice::factory()->create();
    $deleted = pdfInvoiceFixture(DonationEvent::factory()->create());
    $deleted->forceFill(['remote_deleted_at' => now(), 'pdf_disk' => null, 'pdf_path' => null])->save();

    expect(app(DownloadDonorInvoicePdfAction::class)($missingFile))->toBeNull()
        ->and(app(DownloadDonorInvoicePdfAction::class)($noPdf))->toBeNull()
        ->and(app(DownloadDonorInvoicePdfAction::class)($deleted))->toBeNull();
});

it('zips only readable cached pdfs of the selected event', function (): void {
    $event = DonationEvent::factory()->create();
    $first = pdfInvoiceFixture($event);
    $second = pdfInvoiceFixture($event);
    $missing = DonorEventInvoice::factory()->forEvent($event)->create(['pdf_disk' => 'local', 'pdf_path' => 'webling/missing.pdf']);
    $noPdf = DonorEventInvoice::factory()->forEvent($event)->create();
    $otherEvent = pdfInvoiceFixture(DonationEvent::factory()->create());

    $result = app(DownloadEventInvoiceArchiveAction::class)($event, [$first->id, $second->id, $missing->id, $noPdf->id]);

    $zip = new ZipArchive;
    throw_unless($zip->open($result['response']->getFile()->getPathname()) === true, RuntimeException::class);
    $names = [];
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $names[] = $zip->getNameIndex($index);
    }
    $zip->close();

    expect($names)->toHaveCount(2)
        ->and($names)->toContain(sprintf('invoice_DON-%d-%d.pdf', $first->donation_event_id, $first->external_user_id))
        ->and($names)->toContain(sprintf('invoice_DON-%d-%d.pdf', $second->donation_event_id, $second->external_user_id))
        ->and($result['skipped_invoice_ids'])->toBe([$missing->id, $noPdf->id])
        ->and($otherEvent->exists())->toBeTrue();
});

it('rejects selections from another event', function (): void {
    $event = DonationEvent::factory()->create();
    $other = pdfInvoiceFixture(DonationEvent::factory()->create());

    expect(fn () => app(DownloadEventInvoiceArchiveAction::class)($event, [$other->id]))
        ->toThrow(InvalidArgumentException::class, 'nicht zum ausgewählten Anlass');
});

it('rejects an empty selection', function (): void {
    $event = DonationEvent::factory()->create();

    expect(fn () => app(DownloadEventInvoiceArchiveAction::class)($event, null))
        ->toThrow(InvalidArgumentException::class, 'keine Rechnungen gefunden');
});

it('rejects a zip without any readable pdf', function (): void {
    $event = DonationEvent::factory()->create();
    DonorEventInvoice::factory()->forEvent($event)->create(['pdf_disk' => 'local', 'pdf_path' => 'webling/missing.pdf']);

    expect(fn () => app(DownloadEventInvoiceArchiveAction::class)($event))
        ->toThrow(InvalidArgumentException::class, 'Keine der ausgewählten Rechnungen');
});
