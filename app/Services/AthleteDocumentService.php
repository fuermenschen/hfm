<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AthleteDocumentType;
use App\Models\AthleteRegistration;
use App\Settings\InvoiceSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Str;

class AthleteDocumentService
{
    /**
     * @return array{pdf:DomPdfWrapper,filename:string}
     */
    public function render(AthleteRegistration $registration, AthleteDocumentType $type): array
    {
        $registration->loadMissing(['donationEvent', 'externalUser', 'partner', 'sportType']);

        $athlete = $registration->externalUser;
        $event = $registration->donationEvent;
        $viewData = [
            'registration' => $registration,
            'athlete' => $athlete,
            'event' => $event,
            'partnerName' => $registration->partner->name ?? __('app.equal_split_full'),
        ];

        if ($type === AthleteDocumentType::WelcomeLetter) {
            $invoiceSettings = resolve(InvoiceSettings::class);
            $letterheadPath = resource_path('images/letterhead_hfm.svg');

            throw_unless(is_file($letterheadPath), \RuntimeException::class, 'Letterhead asset file could not be resolved.');

            $letterheadData = file_get_contents($letterheadPath);

            throw_if($letterheadData === false, \RuntimeException::class, 'Letterhead asset file could not be read.');

            $associationName = trim($invoiceSettings->creditor_name) !== ''
                ? $invoiceSettings->creditor_name
                : (string) config('app.name');
            $street = trim(implode(' ', array_filter([
                $invoiceSettings->creditor_street,
                $invoiceSettings->creditor_building_number,
            ])));
            $city = trim(implode(' ', array_filter([
                $invoiceSettings->creditor_postal_code,
                $invoiceSettings->creditor_city,
            ])));

            $viewData += [
                'associationName' => $associationName,
                'associationUrl' => rtrim((string) config('app.url'), '/'),
                'associationCity' => $invoiceSettings->creditor_city,
                'officialAddress' => array_values(array_filter([
                    $associationName,
                    trim($invoiceSettings->creditor_care_of) !== '' ? 'c/o '.$invoiceSettings->creditor_care_of : null,
                    $street !== '' ? $street : null,
                    $city !== '' ? $city : null,
                ], fn (mixed $line): bool => is_string($line) && trim($line) !== '')),
                'mailFromAddress' => (string) config('mail.from.address'),
                'eventDate' => $event->starts_at?->format('d.m.Y') ?? '',
                'eventStartTime' => $event->starts_at?->format('G:i') ?? '',
                'eventEndTime' => $event->ends_at?->format('G:i') ?? '',
                'letterheadData' => base64_encode($letterheadData),
                'qrCodeDataUri' => $this->qrCodeDataUri(),
            ];
        }

        /** @var DomPdfWrapper $pdf */
        $pdf = Pdf::loadView($type->view(), $viewData)
            ->setPaper($type->paper(), 'portrait');

        $filename = sprintf(
            '%s_%s_%s_%s.pdf',
            Str::slug($event->slug),
            Str::of($athlete->privacy_name)->replaceMatches('/[^\pL\pN]+/u', '_')->trim('_'),
            Str::of($athlete->public_id_string)->replaceMatches('/[^A-Za-z0-9-]+/', '_')->trim('_'),
            $type->filenameSuffix(),
        );

        return [
            'pdf' => $pdf,
            'filename' => $filename,
        ];
    }

    protected function qrCodeDataUri(): string
    {
        $qrCode = new QrCode(
            data: route('portal.dashboard'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 100,
            margin: 0,
            foregroundColor: new Color(27, 46, 71),
        );

        return (new PngWriter)->write($qrCode)->getDataUri();
    }
}
