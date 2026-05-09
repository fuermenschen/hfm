<?php

use App\Actions\InspectWeblingInvoicePdfAction;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Document;
use Smalot\PdfParser\Page;
use Smalot\PdfParser\Parser;

it('flags placeholder qr slips as invalid', function (): void {
    $pageOne = Mockery::mock(Page::class);
    $pageOne->shouldReceive('getText')->andReturn('TEST Spendenrechnung HfM - Lukas Buehler Fällig bis: 14.04.2026 Total 250.00 CHF');

    $pageTwo = Mockery::mock(Page::class);
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
        'creditor_name' => 'Höhenmeter für Menschen',
    ]);

    expect($result['passed'])->toBeFalse()
        ->and($result['checks']['qr_present'])->toBeFalse()
        ->and($result['issues'])->toContain('QR-Rechnung ist ungültig (Platzhalter oder unvollständige Zahlungsdaten erkannt).');
});

it('passes when qr slip contains expected payer data without placeholder markers', function (): void {
    $pageOne = Mockery::mock(Page::class);
    $pageOne->shouldReceive('getText')->andReturn('TEST Spendenrechnung HfM - Lukas Buehler Fällig bis: 14.04.2026 Fr. 250.00');

    $pageTwo = Mockery::mock(Page::class);
    $pageTwo->shouldReceive('getText')->andReturn('Konto / Zahlbar an CH83 0070 0114 0000 4816 1 Hoehenmeter fuer Menschen Zahlbar durch Lukas Buehler Aarbergergasse 42 3011 Bern');

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
        'creditor_name' => 'Höhenmeter für Menschen',
    ]);

    expect($result['passed'])->toBeTrue()
        ->and($result['checks']['name_correct'])->toBeTrue()
        ->and($result['checks']['address_correct'])->toBeTrue()
        ->and($result['checks']['qr_present'])->toBeTrue();
});

it('returns generic issue and logs parser exceptions', function (): void {
    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'Webling invoice PDF inspection failed'
            && $context['exception'] === 'leaky parser details'
            && $context['exception_class'] === RuntimeException::class
        );

    $parser = Mockery::mock(Parser::class);
    $parser->shouldReceive('parseContent')->once()->andThrow(new RuntimeException('leaky parser details'));

    $action = new InspectWeblingInvoicePdfAction($parser);

    $result = $action('%PDF-fake', [
        'name' => 'Lukas Bühler',
        'address' => 'Aarbergergasse 42',
        'zip' => '3011',
        'city' => 'Bern',
        'amount' => 250.00,
        'due_date' => '14.04.2026',
    ]);

    expect($result['passed'])->toBeFalse()
        ->and($result['issues'])->toContain('PDF konnte nicht automatisch gelesen werden.')
        ->and(collect($result['issues'])->implode(' '))->not->toContain('leaky parser details');
});
