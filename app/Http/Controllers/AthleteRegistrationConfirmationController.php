<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RotateRememberTokenAction;
use App\Models\ExternalUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AthleteRegistrationConfirmationController extends Controller
{
    public function __invoke(Request $request, RotateRememberTokenAction $rotateRememberToken, string $uuid): RedirectResponse
    {
        $externalUser = ExternalUser::query()->where('uuid', $uuid)->firstOrFail();

        auth()->guard('web')->logout();
        $rotateRememberToken($externalUser);
        auth()->guard('external')->login($externalUser, true);
        $request->session()->regenerate();

        return to_route('portal.dashboard');
    }
}
