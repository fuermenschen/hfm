<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetPortalContextAction;
use App\Models\Donation;
use App\Models\DonationEvent;
use App\Services\AthleteShareTextService;
use App\Services\DonationService;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PortalParticipationsController extends Controller
{
    public function __invoke(
        Request $request,
        GetPortalContextAction $portalContext,
        DonationService $donationService,
        AthleteShareTextService $athleteShareText,
    ): Factory|View {
        [$externalUser, $selectedEvent, $viewData] = $portalContext($request);

        $registrations = $externalUser->athleteRegistrations()
            ->whereHas('donationEvent', fn (Builder $query): Builder => $query->where('is_published', true))
            ->when($selectedEvent instanceof DonationEvent, fn (Builder $query): Builder => $query->whereBelongsTo($selectedEvent))
            ->with([
                'donationEvent:id,title,timezone,starts_at,location_city',
                'sportType:id,name',
                'partner:id,name',
                'externalUser:id,public_id',
                'donations' => fn ($query) => $query
                    ->select(['id', 'athlete_registration_id', 'donor_external_user_id', 'amount_per_round', 'amount_min', 'amount_max', 'comment', 'verified'])
                    ->oldest(),
                'donations.donorExternalUser' => fn ($query) => $query->withTrashed()->select(['id', 'first_name', 'last_name', 'public_id']),
                'donations.athleteRegistration:id,rounds_estimated,rounds_done',
            ])
            ->get(['id', 'donation_event_id', 'external_user_id', 'sport_type_id', 'partner_id', 'rounds_estimated', 'rounds_done', 'comment', 'verified'])
            ->sortByDesc('donationEvent.starts_at')
            ->values()
            ->map(function ($registration) use ($athleteShareText, $donationService): array {
                $donations = $registration->donations->map(fn (Donation $donation): array => [
                    'donor' => sprintf('%s (%s)', $donation->donorExternalUser->privacy_name, $donation->donorExternalUser->public_id_string),
                    'amountPerRound' => (float) $donation->amount_per_round,
                    'amountMin' => $donation->amount_min !== null ? (float) $donation->amount_min : null,
                    'amountMax' => $donation->amount_max !== null ? (float) $donation->amount_max : null,
                    'estimatedAmount' => $donationService->calculateEstimatedAmount($donation),
                    'currentAmount' => $donationService->calculateActualAmount($donation),
                    'comment' => $donation->comment,
                    'verified' => (bool) $donation->verified,
                ]);

                return [
                    'id' => (int) $registration->id,
                    'event' => $registration->donationEvent->title,
                    'date' => $registration->donationEvent->starts_at?->format('d.m.Y'),
                    'sport' => $registration->sportType->name,
                    'partner' => $registration->partner->name ?? 'Alle Partnerorganisationen',
                    'roundsEstimated' => (int) $registration->rounds_estimated,
                    'roundsDone' => (int) $registration->rounds_done,
                    'comment' => $registration->comment,
                    'verified' => (bool) $registration->verified,
                    'shareTexts' => $registration->verified ? $athleteShareText->templates($registration) : [],
                    'donations' => $donations->all(),
                    'donationCount' => $donations->count(),
                    'pendingDonationCount' => $donations->where('verified', false)->count(),
                    'estimatedDonationAmount' => $donations->sum('estimatedAmount'),
                ];
            });

        return view('pages.portal.participations', [
            ...$viewData,
            'registrations' => $registrations,
        ]);
    }
}
