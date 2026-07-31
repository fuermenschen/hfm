<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Donation;
use App\Models\DonationEvent;
use App\Models\ExternalUser;
use App\Services\DonationService;
use Illuminate\Database\Eloquent\Builder;

class GetPortalDashboardDataAction
{
    public function __construct(public DonationService $donationService) {}

    /**
     * @return array{
     *     receivedDonationCount: int,
     *     pendingReceivedDonationCount: int,
     *     estimatedReceivedAmount: float,
     *     currentReceivedAmount: float,
     *     hasCompletedRounds: bool,
     *     ownDonationCount: int,
     *     pendingOwnDonationCount: int,
     *     pendingParticipations: array<int, array{id: int, event: string, eventDate: ?string, sport: string, partner: string, roundsEstimated: int}>,
     *     pendingDonations: array<int, array{id: int, event: string, eventDate: ?string, athlete: string, estimatedAmount: float, amountMax: ?float}>
     * }
     */
    public function __invoke(ExternalUser $externalUser, ?DonationEvent $selectedEvent): array
    {
        $receivedDonationsQuery = Donation::query()
            ->whereHas('athleteRegistration', function (Builder $query) use ($externalUser, $selectedEvent): void {
                $query
                    ->where('external_user_id', $externalUser->id)
                    ->whereHas('donationEvent', fn (Builder $query): Builder => $query->where('is_published', true))
                    ->when($selectedEvent instanceof DonationEvent, fn (Builder $query): Builder => $query->where('donation_event_id', $selectedEvent->id));
            });

        $receivedDonations = (clone $receivedDonationsQuery)
            ->where('verified', true)
            ->with('athleteRegistration:id,rounds_estimated,rounds_done')
            ->get(['id', 'athlete_registration_id', 'amount_per_round', 'amount_min', 'amount_max']);

        $ownDonationsQuery = $externalUser->donationsAsDonor()
            ->whereHas('athleteRegistration.donationEvent', fn (Builder $query): Builder => $query->where('is_published', true))
            ->when($selectedEvent instanceof DonationEvent, function ($query) use ($selectedEvent): void {
                $query->whereHas('athleteRegistration', fn (Builder $query): Builder => $query->where('donation_event_id', $selectedEvent->id));
            });

        $pendingParticipations = $externalUser->athleteRegistrations()
            ->where('verified', false)
            ->whereHas('donationEvent', fn (Builder $query): Builder => $query->where('is_published', true))
            ->with([
                'donationEvent:id,title,timezone,starts_at',
                'sportType:id,name',
                'partner:id,name',
            ])
            ->latest()
            ->get(['id', 'donation_event_id', 'sport_type_id', 'partner_id', 'rounds_estimated'])
            ->map(fn ($registration): array => [
                'id' => (int) $registration->id,
                'event' => $registration->donationEvent->title,
                'eventDate' => $registration->donationEvent->starts_at?->translatedFormat('j. F Y'),
                'sport' => $registration->sportType->name,
                'partner' => $registration->partner->name ?? 'Alle Partnerorganisationen',
                'roundsEstimated' => (int) $registration->rounds_estimated,
            ])
            ->all();

        $pendingDonations = $externalUser->donationsAsDonor()
            ->where('verified', false)
            ->whereHas('athleteRegistration.donationEvent', fn (Builder $query): Builder => $query->where('is_published', true))
            ->with([
                'athleteRegistration:id,donation_event_id,external_user_id,rounds_estimated',
                'athleteRegistration.donationEvent:id,title,timezone,starts_at',
                'athleteRegistration.externalUser' => fn ($query) => $query->withTrashed()->select(['id', 'first_name', 'last_name', 'public_id']),
            ])
            ->latest()
            ->get(['id', 'athlete_registration_id', 'amount_per_round', 'amount_min', 'amount_max'])
            ->map(fn (Donation $donation): array => [
                'id' => (int) $donation->id,
                'event' => $donation->athleteRegistration->donationEvent->title,
                'eventDate' => $donation->athleteRegistration->donationEvent->starts_at?->translatedFormat('j. F Y'),
                'athlete' => sprintf(
                    '%s (%s)',
                    $donation->athleteRegistration->externalUser->privacy_name,
                    $donation->athleteRegistration->externalUser->public_id_string,
                ),
                'estimatedAmount' => $this->donationService->calculateEstimatedAmount($donation),
                'amountMax' => $donation->amount_max !== null ? (float) $donation->amount_max : null,
            ])
            ->all();

        return [
            'receivedDonationCount' => $receivedDonations->count(),
            'pendingReceivedDonationCount' => (clone $receivedDonationsQuery)->where('verified', false)->count(),
            'estimatedReceivedAmount' => $this->donationService->calculateEstimatedTotal($receivedDonations),
            'currentReceivedAmount' => $this->donationService->calculateActualTotal($receivedDonations),
            'hasCompletedRounds' => $receivedDonations->contains(
                fn (Donation $donation): bool => (int) $donation->athleteRegistration->rounds_done > 0,
            ),
            'ownDonationCount' => (clone $ownDonationsQuery)->where('verified', true)->count(),
            'pendingOwnDonationCount' => (clone $ownDonationsQuery)->where('verified', false)->count(),
            'pendingParticipations' => $pendingParticipations,
            'pendingDonations' => $pendingDonations,
        ];
    }
}
