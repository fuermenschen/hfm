<?php

namespace App\Http\Controllers;

use App\Actions\GetCurrentEventPublicDataAction;
use App\Models\Athlete;
use App\Models\Donation;
use App\Services\CurrentDonationEventService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __construct(
        private CurrentDonationEventService $eventService,
        private GetCurrentEventPublicDataAction $publicDataAction,
    ) {}

    public function index(): View
    {
        $athleteCount = Schema::hasTable('athletes') ? Athlete::count() : 0;
        $donationCount = Schema::hasTable('donations') ? Donation::count() : 0;

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
