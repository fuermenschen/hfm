<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetCurrentEventPublicDataAction;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Services\AthleteService;
use App\Services\PublicDonationEventService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function __construct(
        private PublicDonationEventService $eventService,
        private GetCurrentEventPublicDataAction $publicDataAction,
        private AthleteService $athleteService,
    ) {}

    public function index(): View
    {
        $athleteCount = Schema::hasTable('athlete_registrations') ? $this->athleteService->count() : 0;
        $donationCount = Schema::hasTable('donations') ? Donation::query()->count() : 0;

        $event = $this->eventService->homepage();
        $editions = $this->eventService->resolve();
        $publicData = ($this->publicDataAction)($event);

        return view('home', [
            'athleteCount' => $athleteCount,
            'donationCount' => $donationCount,
            'publicDonationEvent' => $event,
            'publicEventLifecycle' => $event instanceof DonationEvent ? $this->eventService->lifecycle($event) : null,
            'publicPageTitle' => $event instanceof DonationEvent ? $event->title.' · '.$event->starts_at->format('Y') : config('app.name'),
            'publicEventPartners' => $publicData['partners'],
            'publicEventSponsors' => $publicData['sponsors'],
            'historicalEvents' => $editions['historical'],
            'hasNextPublishedEvent' => $editions['upcoming'] !== null || $editions['live'] !== null,
        ]);
    }
}
