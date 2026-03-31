<?php

use App\Actions\InspectWeblingInvoicePdfAction;
use Smalot\PdfParser\Document;
use Smalot\PdfParser\Parser;

it('flags placeholder qr slips as invalid', function (): void {
    $pageOne = Mockery::mock();
    $pageOne->shouldReceive('getText')->andReturn('TEST Spendenrechnung HfM - Lukas Buehler Fällig bis: 14.04.2026 Total 250.00 CHF');

    $pageTwo = Mockery::mock();
    $pageTwo->shouldReceive('getText')->andReturn('Konto / Zahlbar an CH83 0070 0114 0000 4816 1 Platzhalter QR-Rechnung Bitte fuelle die QR-Rechnungsdaten ein NICHT ZUR ZAHLUNG VERWENDEN Zahlbar durch - - -');

    $document = Mockery::mock(Document::class);
    $document->shouldReceive('getPages')->andReturn([$pageOne, $pageTwo]);

    $parser = Mockery::mock(Parser::class);
    $parser->shouldReceive('parseContent')->once()->andReturn($document);

    $action = new InspectWeblingInvoicePdfAction($parser);

    $result = $action('%PDF-fake', [
        'name' => 'Lukas Buehler',
        'address' => 'Aarbergergasse 42',
        'zip' => '3011',
        'city' => 'Bern',
        'amount' => 250.00,
        'due_date' => '14.04.2026',
        'creditor_name' => 'Hohenmeter fur Menschen',
    ]);

    expect($result['passed'])->toBeFalse()
        ->and($result['checks']['qr_present'])->toBeFalse()
        ->and($result['issues'])->toContain('QR-Rechnung ist ungültig (Platzhalter oder unvollständige Zahlungsdaten erkannt).');
});

it('passes when qr slip contains expected payer data without placeholder markers', function (): void {
    $pageOne = Mockery::mock();
    $pageOne->shouldReceive('getText')->andReturn('TEST Spendenrechnung HfM - Lukas Buehler Fällig bis: 14.04.2026 Fr. 250.00');

    $pageTwo = Mockery::mock();
    $pageTwo->shouldReceive('getText')->andReturn('Konto / Zahlbar an CH83 0070 0114 0000 4816 1 Hohenmeter fur Menschen Zahlbar durch Lukas Buehler Aarbergergasse 42 3011 Bern');

    $document = Mockery::mock(Document::class);
    $document->shouldReceive('getPages')->andReturn([$pageOne, $pageTwo]);

    $parser = Mockery::mock(Parser::class);
    $parser->shouldReceive('parseContent')->once()->andReturn($document);

    $action = new InspectWeblingInvoicePdfAction($parser);

    $result = $action('%PDF-fake', [
        'name' => 'Lukas Buehler',
        'address' => 'Aarbergergasse 42',
        'zip' => '3011',
        'city' => 'Bern',
        'amount' => 250.00,
        'due_date' => '14.04.2026',
        'creditor_name' => 'Hohenmeter fur Menschen',
    ]);

    expect($result['passed'])->toBeTrue()
        ->and($result['checks']['name_correct'])->toBeTrue()
        ->and($result['checks']['address_correct'])->toBeTrue()
        ->and($result['checks']['qr_present'])->toBeTrue();
});
