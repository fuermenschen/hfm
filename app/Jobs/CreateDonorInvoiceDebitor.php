<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CollectDonorInvoiceDataAction;
use App\Models\Donor;
use App\Services\Webling\Invoice\Dto\InvoiceCreateData;
use App\Services\Webling\Invoice\WeblingInvoiceService;
use App\Settings\InvoiceSettings;
use App\Settings\WeblingApiSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CreateDonorInvoiceDebitor implements ShouldQueue
{
    use Queueable;

    private CollectDonorInvoiceDataAction $collectDonorInvoiceDataAction;

    public function __construct(public Donor $donor)
    {
        $this->collectDonorInvoiceDataAction = resolve(CollectDonorInvoiceDataAction::class);
    }

    public function handle(): void
    {
        // Early return if debitor already exists
        if ($this->donor->webling_data !== null && isset($this->donor->webling_data['debitor_id'])) {
            return;
        }

        // Collect invoice data
        $invoiceLines = ($this->collectDonorInvoiceDataAction)($this->donor);
        if ($invoiceLines === []) {
            throw new \RuntimeException('No invoice lines for donor ID '.$this->donor->id);
        }

        // Map donation lines to Webling invoice line format
        $lines = [];
        foreach ($invoiceLines as $l) {
            $title = sprintf('%s für %s | %d Runden à Fr. %.2f',
                $l['athlete'],
                $l['partner'],
                $l['rounds'],
                $l['amount_per_round'] ?? 0.0
            );

            if ($l['subtotal'] > $l['total']) {
                $title .= sprintf(' | Max. Fr. %.2f', $l['total']);
            } elseif ($l['subtotal'] < $l['total']) {
                $title .= sprintf(' | Min. Fr. %.2f', $l['total']);
            }

            $lines[] = [
                'amount' => (float) ($l['total'] ?? 0.0),
                'title' => $title,
            ];
        }

        // remove lines with zero amount
        $lines = array_filter($lines, fn (array $l): bool => $l['amount'] > 0);
        if ($lines === []) {
            throw new \RuntimeException('No non-zero amount invoice lines for donor ID '.$this->donor->id);
        }

        // Build recipient address lines
        $country = strtoupper((string) ($this->donor->country_of_residence ?? ''));
        $zip = (string) ($this->donor->zip_code ?? '');
        $prefixedZip = $zip;
        if ($country !== '' && $country !== 'CH') {
            // Prefix non-Swiss ZIPs with the country code (e.g., DE-12345)
            // Avoid double-prefixing if already present
            $normalized = strtoupper($zip);
            if (! str_starts_with($normalized, $country.'-')) {
                $prefixedZip = $country.'-'.ltrim($normalized);
            }
        }

        $addressLines = array_values(array_filter([
            $this->donor->first_name.' '.$this->donor->last_name,
            $this->donor->address,
            $prefixedZip.' '.($this->donor->city),
        ], fn ($v): bool => trim((string) $v) !== ''));

        // Settings
        $settings = resolve(WeblingApiSettings::class);
        $dueDays = resolve(InvoiceSettings::class)->due_days;
        $dueDate = $dueDays ? now()->addDays($dueDays) : now()->addDays(14);

        $dto = InvoiceCreateData::fromArray([
            'title' => 'Spendenrechnung Höhenmeter für Menschen',
            'date' => now(),
            'duedate' => $dueDate,
            'address_lines' => $addressLines,
            'period_id' => $settings->accounting_period_id,
            'invoice_lines' => $lines,
        ]);

        // Create the debitor/invoice
        $response = resolve(WeblingInvoiceService::class)->createInvoice($dto);

        if ($response->status() === 201) {
            $weblingData = $this->donor->webling_data ?? [];

            $debitorId = $response->json();
            if (is_array($debitorId) && isset($debitorId['id'])) {
                $debitorId = $debitorId['id'];
            }

            $debitorId = (int) $debitorId;

            $weblingData['debitor_id'] = $debitorId;

            // Also store direct URL to the debitor object in Webling
            $baseUrl = rtrim($settings->api_url, '/');
            $periodId = (int) $settings->accounting_period_id;
            $weblingData['debitor_url'] = sprintf('%s/admin#/accounting/%d/debitor/:debitor/editor/%d', $baseUrl, $periodId, $debitorId);

            $this->donor->webling_data = $weblingData;
            $this->donor->save();
        } else {
            // Log unexpected non-error response codes (e.g., 1xx/2xx other than 201 or 3xx)
            Log::warning('Unexpected response when creating Webling debitor for donor', [
                'donor_id' => $this->donor->id,
                'expected_status' => 201,
                'actual_status' => $response->status(),
                'response_excerpt' => substr((string) $response->body(), 0, 500),
            ]);
        }
    }
}
