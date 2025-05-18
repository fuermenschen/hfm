<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = config('app.api_key');

        // Check if the API key is present in the request headers
        if (! $request->headers->has('X-API-Key')) {
            return response()->json(['error' => 'API key is missing'], 401);
        }

        // Check if the API key is valid
        if ($request->headers->get('X-API-Key') !== $apiKey) {
            return response()->json(['error' => 'Invalid API key'], 403);
        }

        return $next($request);
    }
}
