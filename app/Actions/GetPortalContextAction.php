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

        $eventParameters = $selectedEvent !== null
            ? ['anlass' => $selectedEvent->slug]
            : ($request->query->has('anlass') ? ['anlass' => ''] : []);

        $hasAthleteRegistrations = $externalUser->athleteRegistrations()
            ->whereHas('donationEvent', fn (Builder $query): Builder => $query->where('is_published', true))
            ->exists();

        $hasOwnDonations = $externalUser->donationsAsDonor()
            ->whereHas('athleteRegistration.donationEvent', fn (Builder $query): Builder => $query->where('is_published', true))
            ->exists();

        $hour = now()->hour;

        return [$externalUser, $selectedEvent, [
            'firstName' => $externalUser->first_name,
            'greeting' => match (true) {
                $hour >= 17 => 'Guete Abig ',
                $hour >= 12 => 'Hoi ',
                $hour >= 4 => 'Guete Morge ',
                default => 'Hoi ',
            },
            'events' => $events,
            'selectedEvent' => $selectedEvent,
            'selectedEventSlug' => $selectedEvent?->slug,
            'eventParameters' => $eventParameters,
            'hasAthleteRegistrations' => $hasAthleteRegistrations,
            'hasOwnDonations' => $hasOwnDonations,
            'athleteRegistrationOpen' => $currentEvent?->athleteRegistrationIsOpen() ?? false,
            'donorRegistrationOpen' => $currentEvent?->donorRegistrationIsOpen() ?? false,
        ]];
    }
}
