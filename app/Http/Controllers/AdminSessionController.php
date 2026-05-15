<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminSessionController extends Controller
{
    public function store(Request $request, string $uuid): RedirectResponse
    {
        $user = User::query()->where('uuid', $uuid)->firstOrFail();

        auth()->guard('web')->login($user, true);

        // Default request intentional: signed middleware + whereUuid route constraint handle input validation.
        $request->session()->regenerate();

        return to_route('admin.dashboard');
    }
}
