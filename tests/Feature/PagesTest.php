<?php

use App\Models\ExternalUser;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Laravel\Pulse\Http\Middleware\Authorize;
use Opcodes\LogViewer\Http\Middleware\AuthorizeLogViewer;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\seed;

test('all public routes are accessible', function () {
    // Get all registered routes
    $routes = Route::getRoutes()->getRoutes();

    foreach ($routes as $route) {
        // Skip routes that are not GET requests
        if (! in_array('GET', $route->methods())) {
            continue;
        }

        // Skip routes with parameters
        if (str_contains($route->uri, '{')) {
            continue;
        }

        // Skip authenticated routes (handled in a separate test)
        if (collect($route->middleware())->contains(fn (string $middleware): bool => str_starts_with($middleware, 'auth')) ||
            in_array(Authorize::class, $route->middleware()) ||
            in_array(AuthorizeLogViewer::class, $route->middleware())) {
            continue;
        }

        // skip pulse route
        if ($route->uri == 'admin/pulse') {
            continue;
        }

        // Skip routes with API key middleware
        if (in_array('api-key', $route->middleware())) {
            continue;
        }

        // Skip routes guarded by active-event middleware (tested separately)
        if (in_array('active-event', $route->middleware())) {
            continue;
        }

        // Skip debug routes
        if (str_starts_with($route->uri, '_ignition') ||
            str_starts_with($route->uri, '_debugbar') ||
            str_starts_with($route->uri, 'flux/') ||
            str_starts_with($route->uri, 'livewire/')) {
            continue;
        }

        // Test the route
        $response = get($route->uri);

        // Assert the response is successful
        $response->assertSuccessful();
    }
});

// Ensure all authenticated routes are accessible for a signed-in user
test('all authenticated routes are accessible when logged in', function () {
    $routes = Route::getRoutes()->getRoutes();

    $user = User::factory()->create();
    actingAs($user);

    foreach ($routes as $route) {
        // Only GET routes
        if (! in_array('GET', $route->methods())) {
            continue;
        }

        // Only routes with auth:web middleware or Pulse Authorize middleware
        if (! in_array('auth:web', $route->middleware(), true) &&
            ! in_array(Authorize::class, $route->middleware()) &&
            ! in_array(AuthorizeLogViewer::class, $route->middleware())) {
            continue;
        }

        // Skip parameterized routes
        if (str_contains($route->uri, '{')) {
            continue;
        }

        // Skip signed routes that require a valid signature query string
        if (in_array('signed', $route->middleware(), true)) {
            continue;
        }

        // Skip debug routes
        if (str_starts_with($route->uri, '_ignition') ||
            str_starts_with($route->uri, '_debugbar') ||
            str_starts_with($route->uri, 'flux/') ||
            str_starts_with($route->uri, 'livewire/')) {
            continue;
        }

        $response = get($route->uri);
        $response->assertSuccessful();
    }
});

test('authenticated routes are protected', function () {
    $routes = Route::getRoutes()->getRoutes();

    foreach ($routes as $route) {
        // Skip routes that are not GET requests
        if (! in_array('GET', $route->methods())) {
            continue;
        }

        // Only test routes with auth middleware, Pulse Authorize, or Log Viewer Authorize middleware
        if (! collect($route->middleware())->contains(fn (string $middleware): bool => str_starts_with($middleware, 'auth')) &&
            ! in_array(Authorize::class, $route->middleware()) &&
            ! in_array(AuthorizeLogViewer::class, $route->middleware())) {
            continue;
        }

        // Skip parameterized routes
        if (str_contains($route->uri, '{')) {
            continue;
        }

        // Skip debug and framework routes
        if (str_starts_with($route->uri, '_ignition') ||
            str_starts_with($route->uri, '_debugbar') ||
            str_starts_with($route->uri, 'flux/') ||
            str_starts_with($route->uri, 'livewire/')) {
            continue;
        }

        // Test the route without authentication
        $response = get($route->uri);

        // Assert unauthenticated users cannot access (either redirect to login or 403 forbidden)
        if ($response->status() === 403) {
            $response->assertForbidden();
        } else {
            $response->assertRedirect(route('login'));
        }
    }
});

test('all external authenticated routes are accessible for external users', function () {
    $routes = Route::getRoutes()->getRoutes();

    $externalUser = ExternalUser::factory()->create();
    actingAs($externalUser, 'external');

    foreach ($routes as $route) {
        if (! in_array('GET', $route->methods())) {
            continue;
        }

        if (! in_array('auth:external', $route->middleware(), true)) {
            continue;
        }

        if (str_contains($route->uri, '{')) {
            continue;
        }

        if (in_array('signed', $route->middleware(), true)) {
            continue;
        }

        $response = get($route->uri);
        $response->assertSuccessful();
    }
});

test('parameterized signed login routes can be accessed with valid parameters', function () {
    seed();

    $externalUser = ExternalUser::factory()->create();
    $response = get(
        URL::temporarySignedRoute('portal.login.uuid', now()->addMinutes(15), ['uuid' => $externalUser->uuid])
    );

    $response->assertRedirect(route('portal.dashboard'));
});

test('api-key middleware works', function () {

    // Test without API key
    $response = get(route('queue-worker'));
    $response->assertStatus(401);

    // Test with invalid API key
    $response = get(route('queue-worker'), [
        'X-API-Key' => 'invalid-key',
    ]);
    $response->assertStatus(403);

    // Test with valid API key
    $response = get(route('queue-worker'), [
        'X-API-Key' => config('app.api_key'),
    ]);
    $response->assertSuccessful();
});
