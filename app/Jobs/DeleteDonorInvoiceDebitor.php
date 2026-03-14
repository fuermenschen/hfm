<?php

namespace App\Jobs;

use App\Models\Donor;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteDonorInvoiceDebitor implements ShouldQueue
{
    use Queueable;

    public function __construct(public Donor $donor) {}

    public function handle(): void
    {
        $weblingData = $this->donor->webling_data ?? [];

        $changed = false;

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
            $changed = true;
        }

        // If no debitor_id present, just persist any local cleanup and return
        if (! isset($weblingData['debitor_id']) || ! $weblingData['debitor_id']) {
            if ($changed) {
                $this->donor->webling_data = $weblingData;
                $this->donor->save();
            }

            return;
        }

        $debitorId = (int) $weblingData['debitor_id'];

        // Call Webling API to delete debitor
        try {
            $response = app(WeblingInvoiceService::class)->deleteInvoice($debitorId);
            $responseStatus = $response->status();
        } catch (\Throwable $e) {
            if ($e->getCode() === 404) {
                $responseStatus = 404;
            } else {
                throw $e;
            }
        }
        if (in_array($responseStatus, [204, 404], true)) {
            // Consider 204 No Content and 404 Not Found as successful deletions
            unset($weblingData['debitor_id']);
            unset($weblingData['debitor_url']);
            $this->donor->webling_data = $weblingData;
            $this->donor->save();

            return;
        }

        Log::warning('Unexpected response when deleting Webling debitor for donor', [
            'donor_id' => $this->donor->id,
            'debitor_id' => $debitorId,
            'expected_statuses' => [204, 404],
            'actual_status' => $responseStatus,
        ]);

        throw new \RuntimeException('Failed to delete debitor '.$debitorId.' for donor ID '.$this->donor->id);
    }
}
