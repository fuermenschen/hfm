<?php

use App\Actions\DownloadAthleteStoryImageArchiveAction;
use App\Enums\StoryImageVariant;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Services\AthleteStoryImageService;

it('creates both Story image variants for selected athletes in one event', function (): void {
    $event = DonationEvent::factory()->create(['slug' => '2026']);
    $athlete = ExternalUser::factory()->create();
    $registration = AthleteRegistration::factory()->forEvent($event)->forExternalUser($athlete)->create();

    $storyImages = Mockery::mock(AthleteStoryImageService::class);
    foreach (StoryImageVariant::cases() as $variant) {
        $storyImages->shouldReceive('build')
            ->once()
            ->with(Mockery::on(fn (AthleteRegistration $value): bool => $value->is($registration)), $variant)
            ->andReturn([
                'contents' => $variant->value,
                'filename' => $variant->value.'.jpg',
            ]);
    }
    app()->instance(AthleteStoryImageService::class, $storyImages);

    $response = app(DownloadAthleteStoryImageArchiveAction::class)($event, [$athlete->id]);
    $archive = new ZipArchive;
    $archive->open($response->getFile()->getPathname());

    expect($archive->numFiles)->toBe(2)
        ->and($archive->getNameIndex(0))->toBe('light.jpg')
        ->and($archive->getNameIndex(1))->toBe('dark.jpg');

    $archive->close();
    @unlink($response->getFile()->getPathname());
});
