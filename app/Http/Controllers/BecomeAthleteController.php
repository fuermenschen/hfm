<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Services\CurrentDonationEventService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class BecomeAthleteController extends Controller
{
    public function __invoke(CurrentDonationEventService $eventService): Factory|View
    {
        $currentDonationEvent = $eventService->current();

        return view('pages.become-athlete', [
            'currentAthleteRegistration' => $this->currentAthleteRegistration($currentDonationEvent),
        ]);
    }

    protected function currentAthleteRegistration(?DonationEvent $currentDonationEvent): ?AthleteRegistration
    {
        $externalUser = auth()->guard('external')->user();

        if (! $externalUser instanceof ExternalUser || ! $currentDonationEvent instanceof DonationEvent) {
            return null;
        }

        return AthleteRegistration::query()
            ->whereBelongsTo($currentDonationEvent)
            ->whereBelongsTo($externalUser)
            ->first();
    }
}
