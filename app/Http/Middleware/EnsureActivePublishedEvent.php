<?php

namespace App\Http\Middleware;

use App\Services\CurrentDonationEventService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActivePublishedEvent
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app(CurrentDonationEventService::class)->current() === null) {
            return redirect()
                ->route('home')
                ->with('no_active_event_redirected', true);
        }

        return $next($request);
    }
}
