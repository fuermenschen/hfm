<?php

namespace App\Jobs;

use App\Models\Donator;
use App\Services\DonorService;
use App\Services\Webling\Dto\InvoiceCreateData;
use App\Services\Webling\WeblingInvoiceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateDonorInvoice implements ShouldQueue
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
        // load invoice data (domain-specific donation lines)
        $invoiceLines = $this->donorService->collectInvoiceData($this->donor);

        // Map donation lines to Webling invoice line format
        $lines = [];
        foreach ($invoiceLines as $l) {
            $lines[] = [
                'amount' => (float) ($l['total'] ?? 0.0),
                'title' => sprintf('%s für %s (%d Runden à Fr. %.2f)',
                    $l['athlete'],
                    $l['partner'],
                    $l['rounds'],
                    $l['amount_per_round'] ?? 0.0),
            ];
        }

        // Build recipient address lines (simple example; adjust as needed if Donator has address fields)
        $addressLines = array_values(array_filter([
            $this->donor->firstname.' '.$this->donor->lastname,
            $this->donor->address ?? null,
            ($this->donor->zip ?? null).' '.($this->donor->city ?? ''),
        ], fn ($v) => $v !== null && trim((string) $v) !== ''));

        // Compose DTO with sensible defaults (title defaults to current year)
        $settings = app(\App\Settings\WeblingApiSettings::class);

        $dto = InvoiceCreateData::fromArray([
            // Leave title empty to trigger DTO default title
            'title' => '',
            'date' => now(),
            'duedate' => now()->copy()->addDays(30),
            'address_lines' => $addressLines,
            // Use current accounting period as parent for debitor when available
            'period_id' => $settings->accounting_period_id ?? 0,
            'invoice_lines' => $lines,
            // Let WeblingInvoiceService fill these from settings if zero
            'accounting_period_id' => 0,
            'debit_account_id' => 0,
            'credit_account_id' => 0,
        ]);

        // Send to Webling API
        $response = app(WeblingInvoiceService::class)->createInvoice($dto);

        dd($response);
    }
}
