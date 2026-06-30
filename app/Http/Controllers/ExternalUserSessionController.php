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

        auth()->guard('web')->logout();
        auth()->guard('external')->login($externalUser, true);

        // Default request intentional: signed middleware + whereUuid route constraint handle input validation.
        $request->session()->regenerate();

        return to_route($this->redirectRoute($request));
    }

    protected function redirectRoute(Request $request): string
    {
        return match ($request->query('redirect')) {
            'become-athlete' => 'become-athlete',
            'become-donor' => 'become-donor',
            default => 'portal.dashboard',
        };
    }

    public function destroy(Request $request): RedirectResponse
    {
        auth()->guard('external')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
