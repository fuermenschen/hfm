<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AthleteDocumentType;
use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Services\AthleteDocumentService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadAthleteDocumentAction
{
    public function __construct(private AthleteDocumentService $documents) {}

    public function __invoke(DonationEvent $event, int $externalUserId, AthleteDocumentType $type): StreamedResponse
    {
        $registration = AthleteRegistration::query()
            ->where('donation_event_id', $event->id)
            ->where('external_user_id', $externalUserId)
            ->with(['donationEvent', 'externalUser', 'partner', 'sportType'])
            ->firstOrFail();

        $document = $this->documents->render($registration, $type);

        return response()->streamDownload(function () use ($document): void {
            echo $document['pdf']->output();
        }, $document['filename'], ['Content-Type' => 'application/pdf']);
    }
}
