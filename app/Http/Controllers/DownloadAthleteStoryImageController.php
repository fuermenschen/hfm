<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StoryImageVariant;
use App\Models\AthleteRegistration;
use App\Services\AthleteStoryImageService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadAthleteStoryImageController extends Controller
{
    public function __invoke(
        AthleteRegistration $athleteRegistration,
        StoryImageVariant $variant,
        AthleteStoryImageService $storyImage,
    ): StreamedResponse {
        $athleteRegistration->loadMissing('donationEvent');

        abort_unless(
            $athleteRegistration->external_user_id === auth('external')->id()
                && $athleteRegistration->donationEvent->is_published === true,
            404,
        );

        $image = $storyImage->build($athleteRegistration, $variant);

        return response()->streamDownload(
            static function () use ($image): void {
                echo $image['contents'];
            },
            $image['filename'],
            ['Content-Type' => 'image/jpeg'],
        );
    }
}
