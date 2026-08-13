<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AthleteDocumentType;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Services\AthleteDocumentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DownloadAthleteDocumentArchiveAction
{
    public function __construct(private AthleteDocumentService $documents) {}

    /**
     * @param  array<int, int>|null  $externalUserIds
     */
    public function __invoke(
        DonationEvent $event,
        AthleteDocumentType $type,
        ?array $externalUserIds = null,
    ): BinaryFileResponse {
        $externalUserIds = $externalUserIds === null
            ? null
            : array_values(array_unique(array_filter(array_map('intval', $externalUserIds), fn (int $id): bool => $id > 0)));

        $query = AthleteRegistration::query()
            ->where('donation_event_id', $event->id)
            ->when($externalUserIds !== null, fn (Builder $query): Builder => $query->whereIn('external_user_id', $externalUserIds));

        $registrationCount = $query->count();

        throw_if($externalUserIds !== null && $registrationCount !== count($externalUserIds), \InvalidArgumentException::class, 'Die ausgewählten Sportler:innen gehören nicht zum ausgewählten Anlass.');

        throw_if($registrationCount === 0, \InvalidArgumentException::class, 'Für diesen Anlass wurden keine Sportler:innen gefunden.');

        $disk = Storage::disk('local');
        $disk->makeDirectory('tmp');

        $relativePath = 'tmp/athlete-documents-'.Str::uuid().'.zip';
        $temporaryPath = $disk->path($relativePath);

        $zip = new ZipArchive;

        try {
            throw_unless($zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, RuntimeException::class, 'Could not open document archive.');

            $query
                ->with(['donationEvent', 'externalUser', 'partner', 'sportType'])
                ->orderBy('id')
                ->chunkById(50, function ($registrations) use ($type, $zip): void {
                    foreach ($registrations as $registration) {
                        $document = $this->documents->render($registration, $type);
                        throw_unless(
                            $zip->addFromString($document['filename'], $document['pdf']->output()),
                            RuntimeException::class,
                            'Could not add document to archive.',
                        );
                    }
                });

            throw_unless($zip->close(), RuntimeException::class, 'Could not finalize document archive.');

            return response()->download(
                $temporaryPath,
                sprintf('%s_%s.zip', Str::slug($event->slug), $type->archiveFilename()),
                ['Content-Type' => 'application/zip'],
            )->deleteFileAfterSend(true);
        } catch (\Throwable $throwable) {
            $zip->close();
            $disk->delete($relativePath);

            throw $throwable;
        }
    }
}
