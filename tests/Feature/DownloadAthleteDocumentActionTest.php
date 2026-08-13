<?php

use App\Actions\DownloadAthleteDocumentAction;
use App\Actions\DownloadAthleteDocumentArchiveAction;
use App\Enums\AthleteDocumentType;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Services\AthleteDocumentService;
use Barryvdh\DomPDF\PDF;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('downloads a document only for the selected event registration', function (): void {
    $event = DonationEvent::factory()->create(['slug' => '2026']);
    $otherEvent = DonationEvent::factory()->create(['slug' => '2027']);
    $athlete = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forEvent($event)->forExternalUser($athlete)->create();
    AthleteRegistration::factory()->forEvent($otherEvent)->forExternalUser($athlete)->create();

    $pdf = Mockery::mock(PDF::class);
    $pdf->shouldReceive('output')->once()->andReturn('%PDF-test');
    $documents = Mockery::mock(AthleteDocumentService::class);
    $documents->shouldReceive('render')
        ->once()
        ->with(Mockery::on(fn (AthleteRegistration $value): bool => $value->is($registration)), AthleteDocumentType::PersonalizedFlyer)
        ->andReturn(['pdf' => $pdf, 'filename' => '2026_flyer.pdf']);

    app()->instance(AthleteDocumentService::class, $documents);

    $response = app(DownloadAthleteDocumentAction::class)($event, $athlete->id, AthleteDocumentType::PersonalizedFlyer);

    expect($response->headers->get('Content-Disposition'))->toContain('2026_flyer.pdf');
});

it('creates an archive for all or selected athletes in one event', function (): void {
    $event = DonationEvent::factory()->create(['slug' => '2026']);
    $otherEvent = DonationEvent::factory()->create(['slug' => '2027']);
    $selectedAthlete = ExternalUser::factory()->create();
    $otherAthlete = ExternalUser::factory()->create();
    $selectedRegistration = AthleteRegistration::factory()->forEvent($event)->forExternalUser($selectedAthlete)->create();
    $otherRegistration = AthleteRegistration::factory()->forEvent($otherEvent)->forExternalUser($otherAthlete)->create();

    $pdf = Mockery::mock(PDF::class);
    $pdf->shouldReceive('output')->once()->andReturn('%PDF-test');
    $documents = Mockery::mock(AthleteDocumentService::class);
    $documents->shouldReceive('render')
        ->once()
        ->with(Mockery::on(fn (AthleteRegistration $value): bool => $value->is($selectedRegistration)), AthleteDocumentType::WelcomeLetter)
        ->andReturn(['pdf' => $pdf, 'filename' => 'selected.pdf']);

    app()->instance(AthleteDocumentService::class, $documents);

    $response = app(DownloadAthleteDocumentArchiveAction::class)(
        $event,
        AthleteDocumentType::WelcomeLetter,
        [$selectedAthlete->id],
    );
    $archive = new ZipArchive;
    $archive->open($response->getFile()->getPathname());

    expect($archive->numFiles)->toBe(1)
        ->and($archive->getNameIndex(0))->toBe('selected.pdf')
        ->and($otherRegistration->donation_event_id)->not->toBe($event->id);

    $archive->close();
    @unlink($response->getFile()->getPathname());
});

it('rejects selected athletes outside the selected event', function (): void {
    $event = DonationEvent::factory()->create();
    $otherEvent = DonationEvent::factory()->create();
    $athlete = ExternalUser::factory()->asAthlete($otherEvent)->create();

    expect(fn (): mixed => app(DownloadAthleteDocumentArchiveAction::class)(
        $event,
        AthleteDocumentType::PersonalizedFlyer,
        [$athlete->id],
    ))->toThrow(InvalidArgumentException::class);
});

it('downloads welcome letters for the owning confirmed athlete', function (): void {
    $event = DonationEvent::factory()->create(['slug' => '2026']);
    $athlete = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forVerifiedEventUser($event, $athlete)->create();

    $pdf = Mockery::mock(PDF::class);
    $pdf->shouldReceive('output')->once()->andReturn('%PDF-test');
    $documents = Mockery::mock(AthleteDocumentService::class);
    $documents->shouldReceive('render')
        ->once()
        ->with(Mockery::on(fn (AthleteRegistration $value): bool => $value->is($registration)), AthleteDocumentType::WelcomeLetter)
        ->andReturn(['pdf' => $pdf, 'filename' => 'welcome-letter.pdf']);
    app()->instance(AthleteDocumentService::class, $documents);

    actingAs($athlete, 'external');

    get(route('portal.welcome-letter.download', $registration))
        ->assertDownload('welcome-letter.pdf');
});

it('denies welcome letters to other users and unconfirmed athletes', function (): void {
    $event = DonationEvent::factory()->create(['slug' => '2026']);
    $athlete = ExternalUser::factory()->create();
    $stranger = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forEvent($event)->forExternalUser($athlete)->create(['verified' => false]);

    actingAs($stranger, 'external');

    get(route('portal.welcome-letter.download', $registration))->assertNotFound();

    actingAs($athlete, 'external');

    get(route('portal.welcome-letter.download', $registration))->assertNotFound();
});
