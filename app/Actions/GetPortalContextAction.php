<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Services\CurrentDonationEventService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class GetPortalContextAction
{
    /**
     * @return array{ExternalUser, DonationEvent|null, array<string, mixed>}
     */
    public function __invoke(Request $request): array
    {
        $externalUser = auth('external')->user();
        abort_unless($externalUser instanceof ExternalUser, 403);

        $currentEvent = resolve(CurrentDonationEventService::class)->current();
        $events = DonationEvent::query()
            ->select(['id', 'slug', 'title', 'timezone', 'starts_at'])
            ->where('is_published', true)
            ->where(function (Builder $query) use ($externalUser, $currentEvent): void {
                $query
                    ->whereHas('athleteRegistrations', fn (Builder $query): Builder => $query->where('external_user_id', $externalUser->id))
                    ->orWhereHas('athleteRegistrations.donations', fn (Builder $query): Builder => $query->where('donor_external_user_id', $externalUser->id));

                if ($currentEvent instanceof DonationEvent) {
                    $query->orWhere('donation_events.id', $currentEvent->id);
                }
            })
            ->latest('starts_at')
            ->get();

        $selectedEvent = $currentEvent !== null ? $events->firstWhere('id', $currentEvent->id) : null;

        if ($request->query->has('anlass')) {
            $eventSlug = $request->query('anlass');

            if ($eventSlug === null || $eventSlug === '') {
                $selectedEvent = null;
            } else {
                abort_unless(is_string($eventSlug), 404);
                $selectedEvent = $events->firstWhere('slug', $eventSlug);
                abort_unless($selectedEvent instanceof DonationEvent, 404);
            }
        }

        return [$externalUser, $selectedEvent, [
            'firstName' => $externalUser->first_name,
            'greeting' => match (true) {
                (int) date('H') >= 17 => 'Guten Abend ',
                (int) date('H') >= 12 => 'Grüezi ',
                (int) date('H') >= 4 => 'Guten Morgen ',
                default => 'Hallo ',
            },
            'events' => $events,
            'selectedEvent' => $selectedEvent,
            'selectedEventSlug' => $selectedEvent?->slug,
            'hasAthleteRegistrations' => $externalUser->athleteRegistrations()->exists(),
        ]];
    }
}
