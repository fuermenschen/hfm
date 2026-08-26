<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetPortalContextAction;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Services\DonationService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PortalDonationsController extends Controller
{
    public function __invoke(
        Request $request,
        GetPortalContextAction $portalContext,
        DonationService $donationService,
    ): Factory|View {
        [$externalUser, $selectedEvent, $viewData] = $portalContext($request);

        $donations = $externalUser->donationsAsDonor()
            ->whereHas('athleteRegistration.donationEvent', fn (Builder $query): Builder => $query->where('is_published', true))
            ->when($selectedEvent instanceof DonationEvent, function ($query) use ($selectedEvent): void {
                $query->whereHas('athleteRegistration', fn (Builder $query): Builder => $query->whereBelongsTo($selectedEvent));
            })
            ->with([
                'athleteRegistration:id,donation_event_id,external_user_id,sport_type_id,partner_id,rounds_estimated,rounds_done,verified',
                'athleteRegistration.donationEvent:id,title,timezone,starts_at',
                'athleteRegistration.externalUser' => fn ($query) => $query->withTrashed()->select(['id', 'first_name', 'last_name', 'public_id']),
                'athleteRegistration.sportType:id,name',
                'athleteRegistration.partner:id,name',
            ])
            ->get(['id', 'donor_external_user_id', 'athlete_registration_id', 'amount_per_round', 'amount_min', 'amount_max', 'comment', 'verified'])
            ->sortByDesc('athleteRegistration.donationEvent.starts_at')
            ->values()
            ->map(function (Donation $donation) use ($donationService): array {
                $eventStarted = $donation->athleteRegistration->donationEvent->hasStarted();

                return [
                    'id' => (int) $donation->id,
                    'event' => $donation->athleteRegistration->donationEvent->title,
                    'date' => $donation->athleteRegistration->donationEvent->starts_at?->format('d.m.Y'),
                    'athlete' => sprintf(
                        '%s (%s)',
                        $donation->athleteRegistration->externalUser->privacy_name,
                        $donation->athleteRegistration->externalUser->public_id_string,
                    ),
                    'athleteRegistrationId' => (int) $donation->athleteRegistration->id,
                    'athleteVerified' => (bool) $donation->athleteRegistration->verified,
                    'sport' => $donation->athleteRegistration->sportType->name,
                    'partner' => $donation->athleteRegistration->partner->name ?? 'Alle Partnerorganisationen',
                    'amountPerRound' => (float) $donation->amount_per_round,
                    'amountMin' => $donation->amount_min !== null ? (float) $donation->amount_min : null,
                    'amountMax' => $donation->amount_max !== null ? (float) $donation->amount_max : null,
                    'amount' => $eventStarted
                        ? $donationService->calculateActualAmount($donation)
                        : $donationService->calculateEstimatedAmount($donation),
                    'amountLabel' => $eventStarted ? 'Spenden (tatsächlich)' : 'Spenden (geschätzt)',
                    'comment' => $donation->comment,
                    'verified' => (bool) $donation->verified,
                ];
            });

        return view('pages.portal.donations', [
            ...$viewData,
            'donations' => $donations,
        ]);
    }
}
