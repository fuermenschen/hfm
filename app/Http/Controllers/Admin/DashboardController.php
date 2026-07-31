<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\GetDashboardDataAction;
use App\Http\Controllers\Controller;
use App\Models\DonationEvent;
use App\Services\CurrentDonationEventService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        CurrentDonationEventService $currentDonationEventService,
        GetDashboardDataAction $getDashboardData,
    ): Factory|View {
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

        return view('pages.admin.dashboard', $getDashboardData($event));
    }
}
