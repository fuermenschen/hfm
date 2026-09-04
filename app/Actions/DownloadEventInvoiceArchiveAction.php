<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DonationEvent;
use App\Models\DonorEventInvoice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DownloadEventInvoiceArchiveAction
{
    /**
     * Build a ZIP of the cached PDF files of the selected invoices for one event.
     * Invoices without a readable cached PDF are skipped and reported.
     *
     * @param  array<int, int>|null  $invoiceIds
     */
    /**
     * @return array{response:BinaryFileResponse,skipped_invoice_ids:list<int>}
     */
    public function __invoke(DonationEvent $event, ?array $invoiceIds = null): array
    {
        $invoiceIds = $invoiceIds === null
            ? null
            : array_values(array_unique(array_filter(array_map('intval', $invoiceIds), fn (int $id): bool => $id > 0)));

        $query = DonorEventInvoice::query()
            ->where('donation_event_id', $event->id)
            ->when($invoiceIds !== null, fn (Builder $query): Builder => $query->whereIn('id', $invoiceIds));

        $invoiceCount = $query->count();

        throw_if($invoiceIds !== null && $invoiceCount !== count($invoiceIds), InvalidArgumentException::class, 'Die ausgewählten Rechnungen gehören nicht zum ausgewählten Anlass.');

        throw_if($invoiceCount === 0, InvalidArgumentException::class, 'Für diesen Anlass wurden keine Rechnungen gefunden.');

        $disk = Storage::disk('local');
        $disk->makeDirectory('tmp');

        $relativePath = 'tmp/donor-invoices-'.Str::uuid().'.zip';
        $temporaryPath = $disk->path($relativePath);

        $zip = new ZipArchive;
        $added = 0;
        $skippedInvoiceIds = [];

        throw_unless($zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, RuntimeException::class, 'Could not open invoice archive.');

        try {
            $query
                ->orderBy('id')
                ->chunkById(50, function ($invoices) use ($zip, &$added, &$skippedInvoiceIds): void {
                    foreach ($invoices as $invoice) {
                        if ($invoice->pdf_disk === null || $invoice->pdf_path === null) {
                            $skippedInvoiceIds[] = $invoice->id;

                            continue;
                        }

                        $pdfDisk = Storage::disk($invoice->pdf_disk);
                        if (! $pdfDisk->exists($invoice->pdf_path)) {
                            $skippedInvoiceIds[] = $invoice->id;

                            continue;
                        }

                        $fileName = sprintf('invoice_DON-%d-%d.pdf', $invoice->donation_event_id, $invoice->external_user_id);
                        throw_unless(
                            $zip->addFromString($fileName, (string) $pdfDisk->get($invoice->pdf_path)),
                            RuntimeException::class,
                            'Could not add invoice to archive.',
                        );
                        $added++;
                    }
                });

            throw_if($added === 0, InvalidArgumentException::class, 'Keine der ausgewählten Rechnungen hat eine abrufbare PDF-Datei.');

            throw_unless($zip->close(), RuntimeException::class, 'Could not finalize invoice archive.');
        } catch (\Throwable $throwable) {
            $disk->delete($relativePath);

            throw $throwable;
        }

        return [
            'response' => response()->download(
                $temporaryPath,
                sprintf('%s_rechnungen.zip', Str::slug($event->slug)),
                ['Content-Type' => 'application/zip'],
            )->deleteFileAfterSend(true),
            'skipped_invoice_ids' => $skippedInvoiceIds,
        ];
    }
}
