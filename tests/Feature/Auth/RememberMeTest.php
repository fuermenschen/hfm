<?php

use App\Models\ExternalUser;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Cookie as SymfonyCookie;

use function Pest\Laravel\flushSession;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withCookie;

function recallerName(string $guard): string
{
    return "remember_{$guard}_".sha1(SessionGuard::class);
}

function responseRecallerPlain(TestResponse $response, string $guard): string
{
    $cookie = $response->getCookie(recallerName($guard));

    expect($cookie)->not->toBeNull();

    // TestResponse::getCookie() already returns the decrypted value.
    return $cookie->getValue();
}

it('re-authenticates a passwordless user from the remember-me cookie after the session expired', function (string $guard, string $route) {
    $user = $guard === 'web' ? User::factory()->create() : ExternalUser::factory()->create();
    $user->forceFill(['remember_token' => 'valid-token'])->save();

    auth()->guard($guard)->login($user, true);

    $recaller = collect(Cookie::getQueuedCookies())->first(
        fn (SymfonyCookie $cookie): bool => $cookie->getName() === recallerName($guard)
    );

    expect($recaller)->not->toBeNull();

    auth()->forgetGuards();
    flushSession();

    $response = withCookie(recallerName($guard), $recaller->getValue())->get(route($route));

    $response->assertOk();

    expect(auth()->guard($guard)->check())->toBeTrue()
        ->and(auth()->guard($guard)->viaRemember())->toBeTrue();
})->with([
    'admin' => ['web', 'admin.dashboard'],
    'portal' => ['external', 'portal.dashboard'],
]);

it('rotates the portal remember-me token on explicit login so old cookies stop working', function (): void {
    $externalUser = ExternalUser::factory()->create();

    $firstLogin = get(URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), ['uuid' => $externalUser->uuid]));
    $recaller = responseRecallerPlain($firstLogin, 'external');

    auth()->forgetGuards();
    flushSession();

    withCookie(recallerName('external'), $recaller)->get(route('portal.dashboard'));

    expect(auth()->guard('external')->check())->toBeTrue();

    get(URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), ['uuid' => $externalUser->uuid]));

    auth()->forgetGuards();
    flushSession();

    withCookie(recallerName('external'), $recaller)->get(route('portal.dashboard'));

    expect(auth()->guard('external')->check())->toBeFalse();
});

it('keeps the admin remember-me token on explicit login so multi-device admins stay remembered', function (): void {
    $user = User::factory()->create(['remember_token' => 'admin-token']);

    $response = get(URL::temporarySignedRoute('login-uuid', now()->addMinutes(15), ['uuid' => $user->uuid]));

    $response->assertRedirect();
    expect($user->refresh()->remember_token)->toBe('admin-token');

    auth()->forgetGuards();
    flushSession();

    withCookie(recallerName('web'), responseRecallerPlain($response, 'web'))
        ->get(route('admin.dashboard'))
        ->assertOk();

    expect(auth()->guard('web')->check())->toBeTrue();
});

it('invalidates remember cookies on logout', function (string $guard, string $loginRoute, string $logoutRoute) {
    $user = $guard === 'web' ? User::factory()->create() : ExternalUser::factory()->create();
    $user->forceFill(['remember_token' => 'stable-token'])->save();

    $login = get(URL::temporarySignedRoute($loginRoute, now()->addMinutes(15), ['uuid' => $user->uuid]));
    $recaller = responseRecallerPlain($login, $guard);

    post(route($logoutRoute))->assertRedirect();

    expect($user->refresh()->remember_token)->not->toBe('stable-token');

    auth()->forgetGuards();
    flushSession();

    withCookie(recallerName($guard), $recaller)->get(route($guard === 'web' ? 'admin.dashboard' : 'portal.dashboard'));

    expect(auth()->guard($guard)->check())->toBeFalse();
})->with([
    'admin' => ['web', 'login-uuid', 'admin.logout'],
    'portal' => ['external', 'portal.login.uuid', 'portal.logout'],
]);

it('caps the remember-me cookie lifetime per guard', function (string $guard, string $loginRoute, int $expectedMinutes) {
    $user = $guard === 'web' ? User::factory()->create() : ExternalUser::factory()->create();

    $response = get(URL::temporarySignedRoute($loginRoute, now()->addMinutes(15), ['uuid' => $user->uuid]));

    $cookie = $response->getCookie(recallerName($guard));

    expect($cookie)->not->toBeNull();

    $lifetime = $cookie->getExpiresTime() - time();

    expect($lifetime)
        ->toBeGreaterThanOrEqual($expectedMinutes * 60 - 10)
        ->toBeLessThanOrEqual($expectedMinutes * 60 + 10);
})->with([
    'admin 14 days' => ['web', 'login-uuid', 20160],
    'portal 120 days' => ['external', 'portal.login.uuid', 172800],
]);
