<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetCurrentEventPublicDataAction;
use App\Models\DonationEvent;
use App\Services\PublicDonationEventService;
use Illuminate\Contracts\View\View;

class FaqController extends Controller
{
    public function __construct(
        private PublicDonationEventService $eventService,
        private GetCurrentEventPublicDataAction $publicDataAction,
    ) {}

    public function index(): View
    {
        return $this->page($this->eventService->homepage());
    }

    public function show(DonationEvent $donationEvent): View
    {
        abort_unless($donationEvent->is_published, 404);

        return $this->page($donationEvent);
    }

    protected function page(?DonationEvent $event): View
    {
        $publicData = ($this->publicDataAction)($event);

        return view('pages.questions-and-answers', [
            'publicDonationEvent' => $event,
            'publicEventLifecycle' => $event instanceof DonationEvent ? $this->eventService->lifecycle($event) : null,
            'publicEventFaqs' => $publicData['faqs'],
            'historicalEvents' => $this->eventService->resolve()['historical'],
        ]);
    }
}
