<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetPortalContextAction;
use App\Actions\GetPortalEventGroupDataAction;
use App\Models\EventGroup;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PortalEventGroupController extends Controller
{
    public function __invoke(
        Request $request,
        EventGroup $eventGroup,
        GetPortalContextAction $portalContext,
        GetPortalEventGroupDataAction $eventGroupData,
    ): Factory|View {
        [$externalUser, , $viewData] = $portalContext($request);
        Gate::forUser($externalUser)->authorize('view', $eventGroup);
        $groupData = $eventGroupData($eventGroup, $externalUser);

        return view('pages.portal.event-group', ['eventGroup' => $eventGroup, ...$groupData, ...$viewData]);
    }
}
