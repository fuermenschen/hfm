<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
            'hasVerifiedAthletes' => $currentDonationEvent?->athleteRegistrations()
                ->where('verified', true)
                ->exists() ?? false,
        ]);
    }
}
