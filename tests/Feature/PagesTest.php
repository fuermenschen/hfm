<?php

use App\Models\Athlete;
use App\Models\Donation;
use App\Models\Donator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

test('all public routes are accessible', function () {
    // Get all registered routes
    $routes = Route::getRoutes();

    foreach ($routes as $route) {
        // Skip routes that are not GET requests
        if (!in_array('GET', $route->methods())) {
            continue;
        }

        // Skip routes with parameters
        if (str_contains($route->uri, '{')) {
            continue;
        }

        // Skip authenticated routes
        if (in_array('auth', $route->middleware())) {
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

test('authenticated routes are protected', function () {
    $routes = Route::getRoutes();

    foreach ($routes as $route) {
        // Skip routes that are not GET requests
        if (!in_array('GET', $route->methods())) {
            continue;
        }

        // Only test routes with auth middleware
        if (!in_array('auth', $route->middleware())) {
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
