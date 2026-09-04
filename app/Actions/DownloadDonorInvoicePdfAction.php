<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DonorEventInvoice;
use Illuminate\Support\Facades\Storage;

class DownloadDonorInvoicePdfAction
{
    /**
     * Resolve the cached PDF of an invoice for download. Returns null when
     * the invoice has no readable cached file.
     *
     * @return array{disk:string,path:string,absolute_path:string,file_name:string}|null
     */
    public function __invoke(DonorEventInvoice $invoice): ?array
    {
        if ($invoice->remote_deleted_at !== null || $invoice->pdf_disk === null || $invoice->pdf_path === null) {
            return null;
        }

        $disk = Storage::disk($invoice->pdf_disk);
        if (! $disk->exists($invoice->pdf_path)) {
            return null;
        }

        return [
            'disk' => $invoice->pdf_disk,
            'path' => $invoice->pdf_path,
            'absolute_path' => $disk->path($invoice->pdf_path),
            'file_name' => sprintf('invoice_DON-%d-%d.pdf', $invoice->donation_event_id, $invoice->external_user_id),
        ];
    }
}
