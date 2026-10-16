<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

/**
 * Rotates the remember-me token before an explicit magic-link login so that
 * all remember cookies issued to other devices stop working. Called before
 * Auth::login() so the freshly queued recaller cookie carries the new token.
 */
class RotateRememberTokenAction
{
    public function __invoke(Authenticatable $user): void
    {
        $user->forceFill(['remember_token' => Str::random(60)])->save();
    }
}
