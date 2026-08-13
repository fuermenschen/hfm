<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\StoryImageVariant;
use App\Models\AthleteRegistration;
use App\Services\AthleteStoryImageService;
use Illuminate\Http\Response;

class PreviewAthleteStoryImageController extends Controller
{
    public function __invoke(
        AthleteRegistration $athleteRegistration,
        StoryImageVariant $variant,
        AthleteStoryImageService $storyImage,
    ): Response {
        $storyImage->authorizePortalAccess($athleteRegistration);

        return response($storyImage->build($athleteRegistration, $variant)['contents'], 200, [
            'Content-Type' => 'image/jpeg',
        ]);
    }
}
