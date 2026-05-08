<?php

declare(strict_types=1);

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
        if (resolve(CurrentDonationEventService::class)->current() === null) {
            return to_route('home')
                ->with('no_active_event_redirected', true);
        }

        return $next($request);
    }
}
