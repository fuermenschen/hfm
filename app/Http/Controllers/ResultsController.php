<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DonationEvent;
use App\Services\CurrentDonationEventService;
use App\Services\PublicDonationEventService;
use Illuminate\Contracts\View\View;

class ResultsController extends Controller
{
    public function index(CurrentDonationEventService $currentEvent, PublicDonationEventService $publicEvents): View
    {
        $donationEvent = $currentEvent->current() ?? $publicEvents->results();

        return $this->resultsPage($donationEvent, $publicEvents);
    }

    public function show(DonationEvent $donationEvent, PublicDonationEventService $publicEvents): View
    {
        abort_unless($donationEvent->is_published, 404);

        return $this->resultsPage($donationEvent, $publicEvents);
    }

    protected function resultsPage(?DonationEvent $donationEvent, PublicDonationEventService $publicEvents): View
    {
        return view('pages.results', [
            'resultsEvent' => $donationEvent,
            'publicDonationEvent' => $donationEvent,
            'publicEventLifecycle' => $donationEvent instanceof DonationEvent ? $publicEvents->lifecycle($donationEvent) : null,
            'publicPageTitle' => 'Resultate'.($donationEvent instanceof DonationEvent ? ' · '.$donationEvent->starts_at->format('Y') : ''),
        ]);
    }
}
