<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StoryImageVariant;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Services\AthleteStoryImageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DownloadAthleteStoryImageArchiveAction
{
    public function __construct(private AthleteStoryImageService $storyImages) {}

    /**
     * @param  array<int, int>|null  $externalUserIds
     */
    public function __invoke(DonationEvent $event, ?array $externalUserIds = null): BinaryFileResponse
    {
        // Story rendering can exceed PHP's normal web-request time limit.
        set_time_limit(0);

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

        $relativePath = 'tmp/athlete-story-images-'.Str::uuid().'.zip';
        $temporaryPath = $disk->path($relativePath);
        $zip = new ZipArchive;

        try {
            throw_unless($zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, RuntimeException::class, 'Could not open story image archive.');

            $query
                ->with(['donationEvent', 'externalUser'])
                ->orderBy('id')
                ->chunkById(50, function ($registrations) use ($zip): void {
                    foreach ($registrations as $registration) {
                        foreach (StoryImageVariant::cases() as $variant) {
                            $image = $this->storyImages->build($registration, $variant);
                            throw_unless($zip->addFromString($image['filename'], $image['contents']), RuntimeException::class, 'Could not add story image to archive.');
                        }
                    }
                });

            throw_unless($zip->close(), RuntimeException::class, 'Could not finalize story image archive.');

            return response()->download(
                $temporaryPath,
                sprintf('%s_Story-Bilder.zip', Str::slug($event->slug)),
                ['Content-Type' => 'application/zip'],
            )->deleteFileAfterSend(true);
        } catch (\Throwable $throwable) {
            $zip->close();
            $disk->delete($relativePath);

            throw $throwable;
        }
    }
}
