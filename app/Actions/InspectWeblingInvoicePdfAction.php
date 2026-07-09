<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;

class InspectWeblingInvoicePdfAction
{
    public function __construct(public Parser $parser) {}

    /**
     * @param  array{name:string,address:string,zip:string,city:string,amount:float|int|string,due_date:string,creditor_name?:string}  $expected
     * @return array{passed:bool,checks:array{name_correct:bool,address_correct:bool,amount_correct:bool,qr_present:bool,date_correct:bool},issues:list<string>}
     */
    public function __invoke(string $pdfBinary, array $expected): array
    {
        $checks = [
            'name_correct' => false,
            'address_correct' => false,
            'amount_correct' => false,
            'qr_present' => false,
            'date_correct' => false,
        ];

        $issues = [];

        try {
            $document = $this->parser->parseContent($pdfBinary);
            $pages = $document->getPages();
            $pageTexts = array_map(static fn (Page $page): string => $page->getText(), $pages);
        } catch (\Throwable $throwable) {
            Log::warning('Webling invoice PDF inspection failed', [
                'exception' => $throwable->getMessage(),
                'exception_class' => $throwable::class,
            ]);

            $issues[] = 'PDF konnte nicht automatisch gelesen werden.';

            return [
                'passed' => false,
                'checks' => $checks,
                'issues' => $issues,
            ];
        }

        $allText = $this->normalizeText(implode("\n", $pageTexts));
        $lastPageText = $this->normalizeText((string) (end($pageTexts) ?: ''));

        $fullName = $this->normalizeText($expected['name']);
        $address = $this->normalizeText($expected['address']);
        $zipCity = $this->normalizeText(trim($expected['zip'].' '.$expected['city']));
        $dueDate = $this->normalizeText($expected['due_date']);
        $creditorName = $this->normalizeText((string) ($expected['creditor_name'] ?? 'Höhenmeter für Menschen'));

        $expectedAmount = (float) $expected['amount'];
        $amountVariants = [
            $this->normalizeText(number_format($expectedAmount, 2, '.', '').' CHF'),
            $this->normalizeText('CHF '.number_format($expectedAmount, 2, '.', '')),
            $this->normalizeText('Fr. '.number_format($expectedAmount, 2, '.', "'")),
        ];

        $checks['name_correct'] = $fullName !== '' && str_contains($lastPageText, $fullName);
        if (! $checks['name_correct']) {
            $issues[] = 'Name fehlt im Zahlteil der QR-Rechnung.';
        }

        $checks['address_correct'] = $address !== '' && $zipCity !== ''
            && str_contains($lastPageText, $address)
            && str_contains($lastPageText, $zipCity);
        if (! $checks['address_correct']) {
            $issues[] = 'Adresse fehlt im Zahlteil der QR-Rechnung.';
        }

        $checks['amount_correct'] = $this->containsAny($allText, $amountVariants);
        if (! $checks['amount_correct']) {
            $issues[] = 'Betrag konnte im PDF nicht eindeutig gefunden werden.';
        }

        $checks['date_correct'] = $dueDate !== '' && str_contains($allText, $dueDate);
        if (! $checks['date_correct']) {
            $issues[] = 'Fälligkeitsdatum fehlt im PDF.';
        }

        $invalidMarkers = [
            $this->normalizeText('NICHT ZUR ZAHLUNG VERWENDEN'),
            $this->normalizeText('Platzhalter QR-Rechnung'),
            $this->normalizeText('Bitte fülle die QR-Rechnungsdaten ein'),
        ];

        $hasInvalidMarker = $this->containsAny($lastPageText, $invalidMarkers);
        $hasCreditor = $creditorName !== '' && str_contains($lastPageText, $creditorName);
        $hasPayerPlaceholder = str_contains($lastPageText, $this->normalizeText("Zahlbar durch\n-"));

        $checks['qr_present'] = ! $hasInvalidMarker && ! $hasPayerPlaceholder && $hasCreditor;
        if (! $checks['qr_present']) {
            $issues[] = 'QR-Rechnung ist ungültig (Platzhalter oder unvollständige Zahlungsdaten erkannt).';
        }

        return [
            'passed' => ! in_array(false, $checks, true),
            'checks' => $checks,
            'issues' => array_values(array_unique($issues)),
        ];
    }

    protected function normalizeText(string $value): string
    {
        $value = Str::of($value)->lower()->ascii()->toString();
        $value = str_replace(['ae', 'oe', 'ue'], ['a', 'o', 'u'], $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * @param  list<string>  $needles
     */
    protected function containsAny(string $haystack, array $needles): bool
    {
        return array_any($needles, fn (string $needle): bool => $needle !== '' && str_contains($haystack, $needle));
    }
}
