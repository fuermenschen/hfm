<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AthleteRegistration;
use App\Models\DonationEvent;
use App\Services\CurrentDonationEventService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class BecomeDonorController extends Controller
{
    public function __invoke(CurrentDonationEventService $eventService): Factory|View
    {
        $currentDonationEvent = $eventService->current();

        return view('pages.become-donor', [
            'currentDonationEvent' => $currentDonationEvent,
            'hasVerifiedAthletes' => $this->hasVerifiedAthletes($currentDonationEvent),
        ]);
    }

    protected function hasVerifiedAthletes(?DonationEvent $currentDonationEvent): bool
    {
        if (! $currentDonationEvent instanceof DonationEvent) {
            return false;
        }

        return AthleteRegistration::query()
            ->whereBelongsTo($currentDonationEvent)
            ->where('verified', true)
            ->exists();
    }
}
