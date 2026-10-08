<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventSearchIndexing
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is(
            'admin',
            'admin/*',
            'login',
            'login/*',
            'newsletter/abmelden/*',
            'portal',
            'portal/*',
            'queue-worker',
        )) {
            return $next($request);
        }

        $request->attributes->set('robots', 'noindex, nofollow');

        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
