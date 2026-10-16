<?php

use App\Models\ExternalUser;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Cookie;

use function Pest\Laravel\withCookie;

it('re-authenticates a passwordless user from the remember-me cookie after the session expired', function (string $guard, string $route) {
    $user = $guard === 'web' ? User::factory()->create() : ExternalUser::factory()->create();
    $user->forceFill(['remember_token' => 'valid-token'])->save();

    auth()->guard($guard)->login($user, true);

    $recallerName = "remember_{$guard}_".sha1(SessionGuard::class);
    $recaller = collect(Cookie::getQueuedCookies())->first(
        fn (Symfony\Component\HttpFoundation\Cookie $cookie): bool => $cookie->getName() === $recallerName
    );

    expect($recaller)->not->toBeNull();

    auth()->forgetGuards();
    session()->flush();

    $response = withCookie($recallerName, $recaller->getValue())->get(route($route));

    $response->assertOk();

    expect(auth()->guard($guard)->check())->toBeTrue()
        ->and(auth()->guard($guard)->viaRemember())->toBeTrue();
})->with([
    'admin' => ['web', 'admin.dashboard'],
    'portal' => ['external', 'portal.dashboard'],
]);
