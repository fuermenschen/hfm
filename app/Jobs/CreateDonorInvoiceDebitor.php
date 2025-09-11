<?php

namespace App\Jobs;

use App\Models\Donator;
use App\Services\DonorService;
use App\Services\Webling\Dto\InvoiceCreateData;
use App\Services\Webling\WeblingInvoiceService;
use App\Settings\WeblingApiSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateDonorInvoiceDebitor implements ShouldQueue
{
    use Queueable;

    private $donorService;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Donator $donor
    ) {
        $this->donorService = app(DonorService::class);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // check that there's no existing invoice for this donor
        if ($this->donor->webling_data !== null && isset($this->donor->webling_data['debitor_id'])) {
            // Invoice already exists; nothing to do
            return;
        }

        // load invoice data (domain-specific donation lines)
        $invoiceLines = $this->donorService->collectInvoiceData($this->donor);
        if (count($invoiceLines) === 0) {
            // No invoice lines; nothing to do
            throw new \RuntimeException('No invoice lines for donor ID '.$this->donor->id);
        }

        // Map donation lines to Webling invoice line format
        $lines = [];
        foreach ($invoiceLines as $l) {

            $title = sprintf('%s für %s (%d Runden à Fr. %.2f)',
                $l['athlete'],
                $l['partner'],
                $l['rounds'],
                $l['amount_per_round'] ?? 0.0);

            if ($l['subtotal'] > $l['total']) {
                $title .= sprintf(' (Max. Fr. %.2f)', $l['total']);
            } elseif ($l['subtotal'] < $l['total']) {
                $title .= sprintf(' (Min. Fr. %.2f)', $l['total']);
            }

            $lines[] = [
                'amount' => (float) ($l['total'] ?? 0.0),
                'title' => $title,
            ];
        }

        // Build recipient address lines (simple example; adjust as needed if Donator has address fields)
        $addressLines = array_values(array_filter([
            $this->donor->first_name.' '.$this->donor->last_name,
            $this->donor->address,
            ($this->donor->zip_code).' '.($this->donor->city),
        ], fn ($v) => $v !== null && trim((string) $v) !== ''));

        // Compose DTO with sensible defaults (title defaults to current year)
        $settings = app(WeblingApiSettings::class);

        $dto = InvoiceCreateData::fromArray([
            'title' => 'Spendenrechnung Höhenmeter für Menschen',
            'date' => now(),
            'duedate' => $settings->due_days ? now()->addDays($settings->due_days) : now()->addDays(14), 'address_lines' => $addressLines,
            'period_id' => $settings->accounting_period_id,
            'invoice_lines' => $lines,
        ]);

        // Send to Webling API
        $response = app(WeblingInvoiceService::class)->createInvoice($dto);

        // check response
        if ($response->status() === 201) {
            $weblingData = $this->donor->webling_data ?? [];
            $weblingData['debitor_id'] = $response->json();
            $this->donor->webling_data = $weblingData;
            $this->donor->save();
        }
    }
}
