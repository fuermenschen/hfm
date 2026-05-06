<?php

namespace App\Http\Controllers;

use App\Actions\GetCurrentEventPublicDataAction;
use App\Services\CurrentDonationEventService;
use Illuminate\Contracts\View\View;

class FaqController extends Controller
{
    public function __construct(
        private CurrentDonationEventService $eventService,
        private GetCurrentEventPublicDataAction $publicDataAction,
    ) {}

    public function index(): View
    {
        $event = $this->eventService->current();
        $publicData = ($this->publicDataAction)($event);

        return view('pages.questions-and-answers', [
            'currentEventFaqs' => $publicData['faqs'],
        ]);
    }
}
