<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetPortalContextAction;
use App\Actions\GetPortalDashboardDataAction;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function __invoke(
        Request $request,
        GetPortalContextAction $portalContext,
        GetPortalDashboardDataAction $dashboardData,
    ): Factory|View {
        [$externalUser, $selectedEvent, $viewData] = $portalContext($request);

        return view('pages.portal.home', [
            ...$viewData,
            ...$dashboardData($externalUser, $selectedEvent),
        ]);
    }
}
