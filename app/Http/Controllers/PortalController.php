<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExternalUser;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class PortalController extends Controller
{
    public function __invoke(): Factory|View
    {
        /** @var ExternalUser $externalUser */
        $externalUser = auth('external')->user();

        $externalUser->load([
            'athleteRegistrations.donationEvent',
            'athleteRegistrations.sportType',
            'athleteRegistrations.donations.donorExternalUser',
            'donationsAsDonor.athleteRegistration.externalUser',
            'donationsAsDonor.athleteRegistration.donationEvent',
        ]);

        $athleteRegistrationsByEvent = $externalUser->athleteRegistrations
            ->sortBy('donationEvent.starts_at')
            ->groupBy('donation_event_id');

        $donationsAsDonorByEvent = $externalUser->donationsAsDonor
            ->sortBy('athleteRegistration.donationEvent.starts_at')
            ->groupBy(fn ($donation): int => (int) $donation->athleteRegistration?->donation_event_id);

        $eventTitles = $this->eventTitleMap($athleteRegistrationsByEvent, $donationsAsDonorByEvent);

        return view('pages.portal', [
            'externalUser' => $externalUser,
            'athleteRegistrationsByEvent' => $athleteRegistrationsByEvent,
            'donationsAsDonorByEvent' => $donationsAsDonorByEvent,
            'eventTitles' => $eventTitles,
        ]);
    }

    /**
     * @param  Collection<int, Collection<int, mixed>>  $athleteRegistrationsByEvent
     * @param  Collection<int, Collection<int, mixed>>  $donationsAsDonorByEvent
     * @return array<int, string>
     */
    private function eventTitleMap(Collection $athleteRegistrationsByEvent, Collection $donationsAsDonorByEvent): array
    {
        $titles = [];

        foreach ($athleteRegistrationsByEvent as $eventId => $registrations) {
            $title = $registrations->first()?->donationEvent?->title;

            if (is_string($title) && $title !== '') {
                $titles[(int) $eventId] = $title;
            }
        }

        foreach ($donationsAsDonorByEvent as $eventId => $donations) {
            $title = $donations->first()?->athleteRegistration?->donationEvent?->title;

            if (is_string($title) && $title !== '') {
                $titles[(int) $eventId] = $title;
            }
        }

        return $titles;
    }
}
