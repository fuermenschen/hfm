<?php

use App\Models\ExternalUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('logs external users in via signed portal link', function () {
    $externalUser = ExternalUser::factory()->create();

    $url = URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), ['uuid' => $externalUser->uuid]);

    get($url)
        ->assertRedirect(route('portal.dashboard'));

    assertAuthenticatedAs($externalUser, 'external');
});

it('logs external users in via signed portal link and resumes athlete registration', function () {
    $externalUser = ExternalUser::factory()->create();

    $url = URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), [
        'uuid' => $externalUser->uuid,
        'redirect' => 'become-athlete',
    ]);

    get($url)
        ->assertRedirect(route('become-athlete'));

    assertAuthenticatedAs($externalUser, 'external');
});

it('ignores unsupported portal signed login redirects', function () {
    $externalUser = ExternalUser::factory()->create();

    $url = URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), [
        'uuid' => $externalUser->uuid,
        'redirect' => 'admin.dashboard',
    ]);

    get($url)
        ->assertRedirect(route('portal.dashboard'));

    assertAuthenticatedAs($externalUser, 'external');
});

it('allows reusing valid external signed login link within ttl', function () {
    $externalUser = ExternalUser::factory()->create();

    $url = URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), ['uuid' => $externalUser->uuid]);

    get($url)->assertRedirect(route('portal.dashboard'));
    assertAuthenticatedAs($externalUser, 'external');

    auth()->guard('external')->logout();

    get($url)->assertRedirect(route('portal.dashboard'));
    assertAuthenticatedAs($externalUser, 'external');
});

it('rejects expired external signed login links', function () {
    $externalUser = ExternalUser::factory()->create();

    $url = URL::temporarySignedRoute('portal.login.uuid', now()->subMinute(), ['uuid' => $externalUser->uuid]);

    get($url)->assertForbidden();
    assertGuest('external');
});

it('rejects external login links with invalid signature', function () {
    $externalUser = ExternalUser::factory()->create();

    $url = URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), ['uuid' => $externalUser->uuid]);
    $invalidUrl = str_replace($externalUser->uuid, (string) str()->uuid(), $url);

    get($invalidUrl)->assertForbidden();
    assertGuest('external');
});

it('prevents external users from accessing admin routes', function () {
    $externalUser = ExternalUser::factory()->create();

    actingAs($externalUser, 'external');

    get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

it('prevents external users from accessing every admin web route', function () {
    $externalUser = ExternalUser::factory()->create();

    actingAs($externalUser, 'external');

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if (! in_array('GET', $route->methods(), true)) {
            continue;
        }

        if (! str_starts_with($route->uri(), 'admin')) {
            continue;
        }

        if (! in_array('auth:web', $route->middleware(), true)) {
            continue;
        }

        if (str_contains($route->uri(), '{')) {
            continue;
        }

        if (in_array('signed', $route->middleware(), true)) {
            continue;
        }

        $response = get('/'.$route->uri());

        if ($response->status() === 403) {
            $response->assertForbidden();
        } else {
            $response->assertRedirect(route('login'));
        }
    }
});

it('prevents admin users from accessing external write endpoints', function () {
    $user = User::factory()->create();

    actingAs($user, 'web');

    post(route('portal.logout'))
        ->assertRedirect(route('login'));
});

it('prevents admin users from accessing external dashboard routes', function () {
    $user = User::factory()->create();

    actingAs($user, 'web');

    get(route('portal.dashboard'))
        ->assertRedirect(route('login'));
});

it('redirects guests from external write endpoints to login', function () {
    post(route('portal.logout'))
        ->assertRedirect(route('login'));
});

it('redirects guests from external dashboard routes to login', function () {
    get(route('portal.dashboard'))
        ->assertRedirect(route('login'));
});

it('logs external users out from portal logout endpoint', function () {
    $externalUser = ExternalUser::factory()->create();

    actingAs($externalUser, 'external');

    post(route('portal.logout'))
        ->assertRedirect(route('home'));

    assertGuest('external');
});

it('returns not found for valid signed external login URL with unknown uuid', function () {
    $unknownUuid = (string) str()->uuid();
    $url = URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), ['uuid' => $unknownUuid]);

    get($url)->assertNotFound();
    assertGuest('external');
});

it('registers split route files with expected guard middleware', function () {
    $adminRoute = Route::getRoutes()->getByName('admin.dashboard');
    $portalRoute = Route::getRoutes()->getByName('portal.dashboard');
    $homeRoute = Route::getRoutes()->getByName('home');

    expect($adminRoute)->not->toBeNull()
        ->and($portalRoute)->not->toBeNull()
        ->and($homeRoute)->not->toBeNull()
        ->and($adminRoute->middleware())->toContain('auth:web')
        ->and($portalRoute->middleware())->toContain('auth:external')
        ->and($homeRoute->middleware())->not->toContain('auth:web')
        ->and($homeRoute->middleware())->not->toContain('auth:external');

});

it('renders portal page for authenticated external users without registrations or donations', function () {
    $externalUser = ExternalUser::factory()->create([
        'first_name' => 'Alex',
    ]);

    actingAs($externalUser, 'external');

    get(route('portal.dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Hallo Alex')
        ->assertSeeText('Ich bin Sportler:in')
        ->assertSeeText('Ich spende')
        ->assertSeeText('Du hast aktuell keine Sportler:innen-Anmeldungen im Portal.')
        ->assertSeeText('Du hast aktuell keine Spenden im Portal.');
});
