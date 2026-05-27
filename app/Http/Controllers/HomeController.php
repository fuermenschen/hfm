<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetCurrentEventPublicDataAction;
use App\Models\Donation;
use App\Services\AthleteService;
use App\Services\CurrentDonationEventService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __construct(
        private CurrentDonationEventService $eventService,
        private GetCurrentEventPublicDataAction $publicDataAction,
        private AthleteService $athleteService,
    ) {}

    public function index(): View
    {
        $athleteCount = Schema::hasTable('athlete_registrations') ? $this->athleteService->count() : 0;
        $donationCount = Schema::hasTable('donations') ? Donation::query()->count() : 0;

        $event = $this->eventService->current();
        $publicData = ($this->publicDataAction)($event);

        return view('home', [
            'athleteCount' => $athleteCount,
            'donationCount' => $donationCount,
            'currentEventPartners' => $publicData['partners'],
            'currentEventSponsors' => $publicData['sponsors'],
        ]);
    }
}
