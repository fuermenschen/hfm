<?php

namespace App\Jobs;

use App\Models\Donator;
use App\Services\Webling\Letter\LetterBuilder;
use App\Services\Webling\Letter\LetterService;
use App\Settings\InvoiceSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateDonorInvoiceLetter implements ShouldQueue
{
    use Queueable;

    public function __construct(public Donator $donor) {}

    public function handle(): void
    {
        $weblingData = $this->donor->webling_data ?? [];

        // Ensure debitor_id exists
        if (! isset($weblingData['debitor_id']) || ! $weblingData['debitor_id']) {
            throw new \RuntimeException('Missing debitor_id for donor ID '.$this->donor->id);
        }

        $debitorId = (int) $weblingData['debitor_id'];

        // Early return if letter PDF already present
        if (isset($weblingData['letter_pdf']) && is_array($weblingData['letter_pdf'])) {
            return;
        }

        // Build due date (same logic as invoice job)
        $dueDays = app(InvoiceSettings::class)->due_days;
        $dueDate = $dueDays ? now()->addDays($dueDays) : now()->addDays(14);

        try {
            $letterResponse = app(LetterService::class)->createInvoiceLetter(
                'Spendenrechnung Höhenmeter für Menschen',
                function (LetterBuilder $b) use ($dueDate): void {
                    $b->body1("Liebe:r {$this->donor->first_name}\nVielen Dank für deine Unterstützung. Im Anhang findest du die Spendenrechnung.")
                        ->body2('Bitte bezahle bis zum Fälligkeitsdatum. Herzlichen Dank!')
                        ->dueDate($dueDate)
                        ->withQrInvoice(function ($q): void {
                            // Populate debtor details; creditor/IBAN/withAmount fall back to settings
                            $fullName = trim(($this->donor->first_name ?? '').' '.($this->donor->last_name ?? ''));
                            $q->debtorName = $fullName !== '' ? [$fullName] : [];
                            $q->debtorAddress1 = $this->donor->address ? [$this->donor->address] : [];
                            $cityLine = trim(($this->donor->zip_code ?? '').' '.($this->donor->city ?? ''));
                            $q->debtorAddress2 = $cityLine !== '' ? [$cityLine] : [];
                            $q->additionalInformation = 'Spendenrechnung Höhenmeter für Menschen Winterthur 2025';
                        });
                },
                $debitorId
            );

            if ($letterResponse->successful()) {
                $pdfBinary = $letterResponse->body();
                if ($pdfBinary) {
                    $path = 'webling/'.Str::uuid().'.pdf';
                    Storage::disk('local')->put($path, $pdfBinary);

                    $weblingData['letter_pdf'] = [
                        'disk' => 'local',
                        'path' => $path,
                        'size' => strlen($pdfBinary),
                    ];

                    $this->donor->webling_data = $weblingData;
                    $this->donor->save();
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to create Webling letter for debitor', [
                'donor_id' => $this->donor->id,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
