<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Donation;
use App\Models\ExternalUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DonorRegistrationConfirmationController extends Controller
{
    public function __invoke(Request $request, string $uuid, Donation $donation): RedirectResponse
    {
        $externalUser = ExternalUser::query()->where('uuid', $uuid)->firstOrFail();

        throw_if($donation->donor_external_user_id !== $externalUser->id, AuthorizationException::class, 'Diese Spende gehört nicht zu deinem Profil.');

        auth()->guard('web')->logout();
        auth()->guard('external')->login($externalUser, true);
        $request->session()->regenerate();

        return to_route('portal.dashboard');
    }
}
