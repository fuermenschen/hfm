<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ExternalUser;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

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
     * @param  iterable<int|string, mixed>  $athleteRegistrationsByEvent
     * @param  iterable<int|string, mixed>  $donationsAsDonorByEvent
     * @return array<int, string>
     */
    protected function eventTitleMap(iterable $athleteRegistrationsByEvent, iterable $donationsAsDonorByEvent): array
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
