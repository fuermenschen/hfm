<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DonationEvent;
use App\Services\CurrentDonationEventService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        CurrentDonationEventService $currentDonationEventService,
    ): View {
        $event = $currentDonationEventService->current();

        if ($request->query->has('anlass')) {
            $eventSlug = $request->query('anlass');

            if ($eventSlug === null || $eventSlug === '') {
                $event = null;
            } else {
                abort_unless(is_string($eventSlug), 404);

                $event = DonationEvent::query()->where('slug', $eventSlug)->firstOrFail();
            }
        }

        $events = DonationEvent::query()
            ->latest('starts_at')
            ->get(['id', 'title', 'slug', 'timezone', 'starts_at', 'is_published']);
        $hour = Date::now()->hour;
        $greeting = match (true) {
            $hour >= 17 => 'Guten Abend ',
            $hour >= 12 => 'Grüezi ',
            $hour >= 4 => 'Guten Morgen ',
            default => 'Hallo ',
        };

        return view('pages.admin.dashboard', [
            'greeting' => $greeting,
            'events' => $events,
            'selectedEventSlug' => $event?->slug,
        ]);
    }
}
