<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetPortalContextAction;
use App\Enums\GroupMembershipStatus;
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
                'donationEvent:id,slug,title,timezone,starts_at,ends_at,location_city',
                'sportType:id,name',
                'partner:id,name',
                'externalUser:id,public_id',
                'eventGroup' => fn ($query) => $query
                    ->select(['id', 'donation_event_id', 'name'])
                    ->withCount([
                        'athleteRegistrations as accepted_count' => fn ($query) => $query->where('group_membership_status', GroupMembershipStatus::Accepted->value),
                        'athleteRegistrations as pending_count' => fn ($query) => $query->where('group_membership_status', GroupMembershipStatus::Pending->value),
                    ]),
                'donations' => fn ($query) => $query
                    ->select(['id', 'athlete_registration_id', 'donor_external_user_id', 'amount_per_round', 'amount_min', 'amount_max', 'comment', 'verified'])
                    ->oldest(),
                'donations.donorExternalUser' => fn ($query) => $query->withTrashed()->select(['id', 'first_name', 'last_name', 'public_id']),
                'donations.athleteRegistration:id,rounds_estimated,rounds_done',
            ])
            ->get(['id', 'donation_event_id', 'external_user_id', 'sport_type_id', 'partner_id', 'event_group_id', 'group_membership_status', 'group_membership_role', 'rounds_estimated', 'rounds_done', 'comment', 'verified'])
            ->sortByDesc('donationEvent.starts_at')
            ->values()
            ->map(function ($registration) use ($athleteShareText, $donationService): array {
                $eventStarted = $registration->donationEvent->hasStarted();
                $donations = $registration->donations->map(function (Donation $donation) use ($donationService, $eventStarted): array {
                    return [
                        'donor' => sprintf('%s (%s)', $donation->donorExternalUser->privacy_name, $donation->donorExternalUser->public_id_string),
                        'amountPerRound' => (float) $donation->amount_per_round,
                        'amountMin' => $donation->amount_min !== null ? (float) $donation->amount_min : null,
                        'amountMax' => $donation->amount_max !== null ? (float) $donation->amount_max : null,
                        'amount' => $eventStarted
                            ? $donationService->calculateActualAmount($donation)
                            : $donationService->calculateEstimatedAmount($donation),
                        'comment' => $donation->comment,
                        'verified' => (bool) $donation->verified,
                    ];
                });

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
                    'eventEnded' => $registration->donationEvent->hasEnded(),
                    'eventStarted' => $eventStarted,
                    'group' => $registration->eventGroup === null ? null : [
                        'id' => (int) $registration->eventGroup->id,
                        'name' => $registration->eventGroup->name,
                        'status' => $registration->group_membership_status?->value,
                        'role' => $registration->group_membership_role?->value,
                        'acceptedCount' => (int) $registration->eventGroup->accepted_count,
                        'pendingCount' => (int) $registration->eventGroup->pending_count,
                    ],
                    'groupDiscoveryUrl' => route('portal.event-groups.discover', $registration),
                    'welcomeLetterUrl' => route('portal.welcome-letter.download', $registration),
                    'shareTexts' => $registration->verified ? $athleteShareText->templates($registration) : [],
                    'donations' => $donations->all(),
                    'donationCount' => $donations->count(),
                    'pendingDonationCount' => $donations->where('verified', false)->count(),
                    'donationAmount' => $donations->sum('amount'),
                ];
            });

        return view('pages.portal.participations', [
            ...$viewData,
            'registrations' => $registrations,
        ]);
    }
}
