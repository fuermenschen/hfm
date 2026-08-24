<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetPortalContextAction;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PortalEventGroupDiscoveryController extends Controller
{
    public function __invoke(Request $request, AthleteRegistration $athleteRegistration, GetPortalContextAction $portalContext): Factory|View
    {
        [$externalUser, , $viewData] = $portalContext($request);
        abort_unless(
            $athleteRegistration->external_user_id === $externalUser->id
            && $athleteRegistration->verified,
            404,
        );

        $athleteRegistration->load('donationEvent:id,title,timezone,ends_at,is_published');
        abort_unless($athleteRegistration->donationEvent->is_published, 404);

        $groups = EventGroup::query()
            ->whereBelongsTo($athleteRegistration->donationEvent)
            ->withCount(['athleteRegistrations as accepted_count' => fn ($query) => $query->where('group_membership_status', GroupMembershipStatus::Accepted->value)])
            ->orderBy('name')
            ->get(['id', 'donation_event_id', 'name'])
            ->map(fn (EventGroup $group): array => ['id' => $group->id, 'name' => $group->name, 'acceptedCount' => $group->accepted_count]);

        return view('pages.portal.event-group-discovery', [
            ...$viewData,
            'registration' => $athleteRegistration,
            'groups' => $groups,
            'eventEnded' => $athleteRegistration->donationEvent->hasEnded(),
        ]);
    }
}
