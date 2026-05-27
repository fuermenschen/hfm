<?php

use App\Models\ExternalUser;
use App\Services\AthleteDocumentService;
use Barryvdh\DomPDF\PDF;

it('builds welcome letter payload with expected filename and paper format', function (): void {
    $athlete = new ExternalUser([
        'first_name' => 'Anna',
        'last_name' => 'Muster',
    ]);

    $pdf = Mockery::mock(PDF::class);
    $pdf->shouldReceive('loadView')
        ->once()
        ->with('printables.athlete_welcome_letter', Mockery::on(function (array $data) use ($athlete): bool {
            return ($data['athlete'] ?? null) === $athlete;
        }))
        ->andReturnSelf();
    $pdf->shouldReceive('setPaper')
        ->once()
        ->with('a4', 'portrait')
        ->andReturnSelf();

    app()->instance('dompdf.wrapper', $pdf);

    $result = app(AthleteDocumentService::class)->buildWelcomeLetter($athlete);

    expect($result['filename'])->toBe('Anna_Muster_Willkommensbrief.pdf')
        ->and($result['pdf'])->toBe($pdf);
});

it('builds personalized flyer payload with expected filename and paper format', function (): void {
    $athlete = new ExternalUser([
        'first_name' => 'Anna',
        'last_name' => 'Muster',
    ]);

    $pdf = Mockery::mock(PDF::class);
    $pdf->shouldReceive('loadView')
        ->once()
        ->with('printables.athlete_personalized_flyer', Mockery::on(function (array $data) use ($athlete): bool {
            return ($data['athlete'] ?? null) === $athlete;
        }))
        ->andReturnSelf();
    $pdf->shouldReceive('setPaper')
        ->once()
        ->with('a5', 'portrait')
        ->andReturnSelf();

    app()->instance('dompdf.wrapper', $pdf);

    $result = app(AthleteDocumentService::class)->buildPersonalizedFlyer($athlete);

    expect($result['filename'])->toBe('Anna_Muster_Flyer.pdf')
        ->and($result['pdf'])->toBe($pdf);
});
