<?php

namespace App\Jobs;

use App\Models\Donator;
use App\Services\DonorService;
use App\Services\Webling\Invoice\Dto\InvoiceCreateData;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use App\Services\Webling\Letter\LetterService;
use App\Settings\InvoiceSettings;
use App\Settings\WeblingApiSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        // Determine due date from settings once
        $dueDays = app(InvoiceSettings::class)->due_days;
        $dueDate = $dueDays ? now()->addDays($dueDays) : now()->addDays(14);

        $dto = InvoiceCreateData::fromArray([
            'title' => 'Spendenrechnung Höhenmeter für Menschen',
            'date' => now(),
            'duedate' => $dueDate,
            'address_lines' => $addressLines,
            'period_id' => $settings->accounting_period_id,
            'invoice_lines' => $lines,
        ]);

        // Send to Webling API
        $response = app(WeblingInvoiceService::class)->createInvoice($dto);

        // check response
        if ($response->status() === 201) {
            $weblingData = $this->donor->webling_data ?? [];

            $debitorId = $response->json();
            if (is_array($debitorId) && isset($debitorId['id'])) {
                $debitorId = $debitorId['id'];
            }
            $debitorId = (int) $debitorId;

            $weblingData['debitor_id'] = $debitorId;

            // Create a PDF letter for the newly created invoice (debitor)
            try {
                $letterResponse = app(LetterService::class)->createInvoiceLetter(
                    $dto->title ?? 'Spendenrechnung Höhenmeter für Menschen',
                    function (\App\Services\Webling\Letter\LetterBuilder $b) use ($dueDate): void {
                        $b->header("Höhenmeter\nfür Menschen")
                            ->body1("Liebe:r {$this->donor->first_name}\nVielen Dank für deine Unterstützung. Im Anhang findest du die Spendenrechnung.")
                            ->body2('Bitte bezahle bis zum Fälligkeitsdatum. Herzlichen Dank!')
                            ->dueDate($dueDate)
                            ->withQrInvoice(fn ($q) => $q->withAmount = false);
                    },
                    $debitorId
                );

                $weblingData['letter_created'] = $letterResponse->successful();

                // Persist PDF and store handle when successful
                if ($letterResponse->successful()) {
                    $pdfBinary = $letterResponse->body();

                    if (is_string($pdfBinary) && $pdfBinary !== '') {
                        $path = sprintf('webling/letters/%d/%s_invoice.pdf', $debitorId, now()->format('Ymd_His'));
                        Storage::disk('local')->put($path, $pdfBinary);

                        $weblingData['letter_pdf'] = [
                            'disk' => 'local',
                            'path' => $path,
                            'size' => strlen($pdfBinary),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to create Webling letter for debitor', [
                    'donor_id' => $this->donor->id,
                    'exception' => $e->getMessage(),
                ]);
                $weblingData['letter_created'] = false;
            }

            $this->donor->webling_data = $weblingData;
            $this->donor->save();
        }
    }
}
