<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AthleteDocumentType;
use App\Models\AthleteRegistration;
use App\Services\AthleteDocumentService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadAthleteWelcomeLetterController extends Controller
{
    public function __invoke(
        AthleteRegistration $athleteRegistration,
        AthleteDocumentService $documents,
    ): StreamedResponse {
        $athleteRegistration->loadMissing('donationEvent');

        abort_unless(
            $athleteRegistration->external_user_id === auth('external')->id()
                && $athleteRegistration->verified === true
                && $athleteRegistration->donationEvent->is_published === true,
            404,
        );

        $document = $documents->render($athleteRegistration, AthleteDocumentType::WelcomeLetter);
        $pdfContents = $document['pdf']->output();

        return response()->streamDownload(function () use ($pdfContents): void {
            echo $pdfContents;
        }, $document['filename'], ['Content-Type' => 'application/pdf']);
    }
}
