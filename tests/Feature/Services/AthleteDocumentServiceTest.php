<?php

use App\Enums\AthleteDocumentType;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Services\AthleteDocumentService;
use App\Settings\InvoiceSettings;
use Barryvdh\DomPDF\PDF;

it('renders a welcome letter for an event registration', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    $athlete = ExternalUser::factory()->create([
        'first_name' => 'Peter',
        'last_name' => 'Muster',
        'public_id' => '4WUFNB',
    ]);
    $registration = AthleteRegistration::factory()->forEvent($event)->forExternalUser($athlete)->create();
    $settings = app(InvoiceSettings::class);
    $settings->creditor_name = 'Verein für Menschen';
    $settings->creditor_care_of = 'Kai Frehner';
    $settings->creditor_street = 'Rössligasse';
    $settings->creditor_building_number = '6';
    $settings->creditor_postal_code = '8400';
    $settings->creditor_city = 'Winterthur';
    $settings->save();
    config(['app.url' => 'https://example.com']);

    $pdf = Mockery::mock(PDF::class);
    $pdf->shouldReceive('loadView')
        ->once()
        ->with('printables.athlete_welcome_letter', Mockery::on(function (array $data) use ($registration, $athlete, $event): bool {
            return ($data['registration'] ?? null)?->is($registration) === true
                && ($data['athlete'] ?? null)?->is($athlete) === true
                && ($data['event'] ?? null)?->is($event) === true
                && is_string($data['logoData'] ?? null)
                && str_starts_with((string) ($data['qrCodeDataUri'] ?? ''), 'data:image/png;base64,')
                && ($data['officialAddress'] ?? null) === ['Verein für Menschen', 'c/o Kai Frehner', 'Rössligasse 6', '8400 Winterthur']
                && ($data['associationDomain'] ?? null) === 'example.com'
                && ($data['mailFromAddress'] ?? null) === (string) config('mail.from.address');
        }))
        ->andReturnSelf();
    $pdf->shouldReceive('setPaper')->once()->with('a4', 'portrait')->andReturnSelf();

    app()->instance('dompdf.wrapper', $pdf);

    $result = app(AthleteDocumentService::class)->render($registration, AthleteDocumentType::WelcomeLetter);

    expect($result['filename'])->toBe('2026_Peter_M_4WU-FNB_Willkommensbrief.pdf')
        ->and($result['pdf'])->toBe($pdf);
});

it('renders a personalized flyer for an event registration', function (): void {
    $event = DonationEvent::factory()->year(2026)->create();
    $athlete = ExternalUser::factory()->create([
        'first_name' => 'Peter',
        'last_name' => 'Muster',
        'public_id' => '4WUFNB',
    ]);
    $registration = AthleteRegistration::factory()->forEvent($event)->forExternalUser($athlete)->create();

    $pdf = Mockery::mock(PDF::class);
    $pdf->shouldReceive('loadView')
        ->once()
        ->with('printables.athlete_personalized_flyer', Mockery::on(function (array $data) use ($registration, $athlete, $event): bool {
            return ($data['registration'] ?? null)?->is($registration) === true
                && ($data['athlete'] ?? null)?->is($athlete) === true
                && ($data['event'] ?? null)?->is($event) === true;
        }))
        ->andReturnSelf();
    $pdf->shouldReceive('setPaper')->once()->with('a5', 'portrait')->andReturnSelf();

    app()->instance('dompdf.wrapper', $pdf);

    $result = app(AthleteDocumentService::class)->render($registration, AthleteDocumentType::PersonalizedFlyer);

    expect($result['filename'])->toBe('2026_Peter_M_4WU-FNB_Personalisierter_Flyer.pdf')
        ->and($result['pdf'])->toBe($pdf);
});
