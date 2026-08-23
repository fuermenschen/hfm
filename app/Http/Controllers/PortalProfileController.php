<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetPortalContextAction;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PortalProfileController extends Controller
{
    public function show(Request $request, GetPortalContextAction $portalContext): Factory|View
    {
        [, , $viewData] = $portalContext($request);

        return view('pages.portal.profile', $viewData);
    }
}
