<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExternalUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExternalUserSessionController extends Controller
{
    public function store(Request $request, string $uuid): RedirectResponse
    {
        $externalUser = ExternalUser::query()->where('uuid', $uuid)->firstOrFail();

        auth()->guard('external')->login($externalUser, true);

        // Default request intentional: signed middleware + whereUuid route constraint handle input validation.
        $request->session()->regenerate();

        return to_route('portal.dashboard');
    }
}
