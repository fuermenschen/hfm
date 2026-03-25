<?php

namespace App\Jobs;

use App\Actions\CollectDonorInvoiceDataAction;
use App\Models\Donor;
use App\Services\CurrentDonationEventService;
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

    public function __construct(public Donor $donor) {}

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

        $text1 = 'Liebe:r '.($this->donor->first_name ?? '')."\n\nWir schätzen dein Engagement sehr und möchten dir herzlich danken.\nUntenstehend findest du eine Übersicht über deine Spenden.\n\n";

        // Compute the minimum amount (sum of all donation totals)
        $invoiceLines = app(CollectDonorInvoiceDataAction::class)($this->donor);
        $minTotal = 0.0;
        foreach ($invoiceLines as $l) {
            $minTotal += (float) ($l['total'] ?? 0.0);
        }
        $amountStr = 'Fr. '.number_format($minTotal, 2, '.', '');
        $dueStr = $dueDate->format('d.m.Y');

        $text2 = 'Bitte verwende zur Einzahlung den beiliegenden Einzahlungsschein. Die Zahlung des Betrags von mindestens '
            .$amountStr
            .' ist fällig bis am '
            .$dueStr
            .'. Nach Eingang aller Spenden werden wir die Überweisungen an die drei Benefizpartner:innen vornehmen. '
            .'Wir werden dich informieren, wann wir welche Beträge überweisen durften.'
            ."\n\nHerzliche Grüsse\nDas Team von Höhenmeter für Menschen";

        $currentEvent = app(CurrentDonationEventService::class)->current();
        $fallbackInvoiceInfo = 'Spendenrechnung Höhenmeter für Menschen Winterthur 2025';
        $invoiceAdditionalInformation = $currentEvent?->contentValue('invoice.additional_information', $fallbackInvoiceInfo)
            ?? $fallbackInvoiceInfo;

        try {
            $letterResponse = app(LetterService::class)->createInvoiceLetter(
                'Spendenrechnung Höhenmeter für Menschen',
                function (LetterBuilder $b) use ($invoiceAdditionalInformation, $text1, $text2): void {
                    $b->body1($text1)
                        ->body2($text2)
                        ->withQrInvoice(function ($q) use ($invoiceAdditionalInformation): void {
                            $fullName = trim(($this->donor->first_name ?? '').' '.($this->donor->last_name ?? ''));
                            $q->debtorName = $fullName !== '' ? [$fullName] : [];
                            $q->debtorStreet = $this->donor->address ? [$this->donor->address] : [];
                            $q->debtorBuildingNumber = [];
                            $q->debtorPostalCode = $this->donor->zip_code ? [$this->donor->zip_code] : [];
                            $q->debtorCity = $this->donor->city ? [$this->donor->city] : [];
                            $q->additionalInformation = $invoiceAdditionalInformation;
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
            } else {
                // Log unexpected non-error response codes for letter creation
                Log::warning('Unexpected response when creating Webling invoice letter for donor', [
                    'donor_id' => $this->donor->id,
                    'actual_status' => $letterResponse->status(),
                    'response_excerpt' => substr((string) $letterResponse->body(), 0, 500),
                ]);
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
