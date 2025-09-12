<?php

namespace App\Jobs;

use App\Models\Donator;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class DeleteDonorInvoiceDebitor implements ShouldQueue
{
    use Queueable;

    public function __construct(public Donator $donor) {}

    public function handle(): void
    {
        $weblingData = $this->donor->webling_data ?? [];

        // Early return if no debitor_id present
        if (! isset($weblingData['debitor_id']) || ! $weblingData['debitor_id']) {
            return;
        }

        // If a PDF handle exists, delete the file and remove reference
        if (isset($weblingData['letter_pdf']) && is_array($weblingData['letter_pdf'])) {
            $disk = $weblingData['letter_pdf']['disk'] ?? null;
            $path = $weblingData['letter_pdf']['path'] ?? null;
            if ($disk && $path) {
                try {
                    Storage::disk((string) $disk)->delete((string) $path);
                } catch (\Throwable $e) {
                    // Silently ignore storage errors during cleanup
                }
            }
            unset($weblingData['letter_pdf']);
        }

        $debitorId = (int) $weblingData['debitor_id'];

        // Call Webling API to delete debitor
        $response = app(WeblingInvoiceService::class)->deleteInvoice($debitorId);

        if ($response->status() === 204) {
            // Remove debitor_id on successful deletion
            unset($weblingData['debitor_id']);
            $this->donor->webling_data = $weblingData;
            $this->donor->save();

            return;
        }

        throw new \RuntimeException('Failed to delete debitor '.$debitorId.' for donor ID '.$this->donor->id);
    }
}
