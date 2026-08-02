<?php

use App\Http\Middleware\ApiKey;
use App\Http\Middleware\EnsureActivePublishedEvent;
use App\Http\Middleware\EnsureSingleAuthenticatedGuard;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')->group(base_path('routes/admin.php'));
            Route::middleware('web')->group(base_path('routes/portal.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', [
            EnsureSingleAuthenticatedGuard::class,
        ]);

        $middleware->alias([
            'api-key' => ApiKey::class,
            'active-event' => EnsureActivePublishedEvent::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $authSignedRouteNames = [
            'login-uuid',
            'portal.login.uuid',
            'portal.athlete-registration.confirm',
            'portal.donation.confirm',
        ];

        $exceptions->render(function (InvalidSignatureException $exception, Request $request) use ($authSignedRouteNames): ?\Illuminate\Http\Response {
            $route = $request->route();

            if ($route instanceof Illuminate\Routing\Route && in_array($route->getName(), $authSignedRouteNames, true)) {
                $intendedDestination = $request->query('redirect');

                return response()->view('pages.login-link-expired', [
                    'intendedDestination' => in_array($intendedDestination, ['become-athlete', 'become-donor'], true) ? $intendedDestination : null,
                ], 403);
            }

            return null;
        });
    })->create();
