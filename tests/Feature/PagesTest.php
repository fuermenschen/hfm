<?php

use App\Models\Athlete;
use App\Models\Donator;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

test('all public routes are accessible', function () {
    // Get all registered routes
    $routes = Route::getRoutes();

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
        if (in_array('auth', $route->middleware())) {
            continue;
        }

        // Skip routes with API key middleware
        if (in_array('api-key', $route->middleware())) {
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
        $response = $this->get($route->uri);

        // Assert the response is successful
        $response->assertSuccessful();
    }
});

// Ensure all authenticated routes are accessible for a signed-in user
test('all authenticated routes are accessible when logged in', function () {
    $routes = Route::getRoutes();

    $user = User::factory()->create();
    $this->actingAs($user);

    foreach ($routes as $route) {
        // Only GET routes
        if (! in_array('GET', $route->methods())) {
            continue;
        }

        // Only routes with auth middleware
        if (! in_array('auth', $route->middleware())) {
            continue;
        }

        // Skip parameterized routes
        if (str_contains($route->uri, '{')) {
            continue;
        }

        // Skip debug routes
        if (str_starts_with($route->uri, '_ignition') ||
            str_starts_with($route->uri, '_debugbar') ||
            str_starts_with($route->uri, 'flux/') ||
            str_starts_with($route->uri, 'livewire/')) {
            continue;
        }

        $response = $this->get($route->uri);
        $response->assertSuccessful();
    }
});

test('authenticated routes are protected', function () {
    $routes = Route::getRoutes();

    foreach ($routes as $route) {
        // Skip routes that are not GET requests
        if (! in_array('GET', $route->methods())) {
            continue;
        }

        // Only test routes with auth middleware
        if (! in_array('auth', $route->middleware())) {
            continue;
        }

        // Test the route without authentication
        $response = $this->get($route->uri);

        // Assert the response redirects to login
        $response->assertRedirect(route('login'));
    }
});

test('parameterized routes can be accessed with valid parameters', function () {
    // Test athlete route
    Artisan::call('db:seed');
    $athlete = Athlete::factory()->create([
        'verified' => true,
    ]);
    $response = $this->get(route('show-athlete', ['login_token' => $athlete->login_token]));
    $response->assertSuccessful();

    // Test donator route
    $donator = Donator::factory()->create();
    $response = $this->get(route('show-donator', ['login_token' => $donator->login_token]));
    $response->assertSuccessful();

});

test('api-key middleware works', function () {

    // Test without API key
    $response = $this->get(route('queue-worker'));
    $response->assertStatus(401);

    // Test with invalid API key
    $response = $this->withHeaders([
        'X-API-Key' => 'invalid-key',
    ])->get(route('queue-worker'));
    $response->assertStatus(403);

    // Test with valid API key
    $response = $this->withHeaders([
        'X-API-Key' => config('app.api_key'),
    ])->get(route('queue-worker'));
    $response->assertSuccessful();
});
