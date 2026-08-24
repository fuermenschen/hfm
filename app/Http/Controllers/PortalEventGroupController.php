<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GetPortalContextAction;
use App\Enums\GroupMembershipRole;
use App\Enums\GroupMembershipStatus;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PortalEventGroupController extends Controller
{
    public function __invoke(Request $request, EventGroup $eventGroup, GetPortalContextAction $portalContext): Factory|View
    {
        [$externalUser, , $viewData] = $portalContext($request);
        Gate::forUser($externalUser)->authorize('view', $eventGroup);

        $eventGroup->load('donationEvent:id,slug,title,timezone,ends_at,is_published');
        abort_unless($eventGroup->donationEvent->is_published, 404);
        $registration = AthleteRegistration::query()
            ->verifiedForEventUser($eventGroup->donationEvent, $externalUser)
            ->firstOrFail();

        $accepted = $eventGroup->athleteRegistrations()
            ->where('group_membership_status', GroupMembershipStatus::Accepted->value)
            ->with([
                'externalUser' => fn ($query) => $query->select(['id', 'first_name', 'last_name', 'public_id']),
                'sportType:id,name',
            ])
            ->get(['id', 'external_user_id', 'event_group_id', 'sport_type_id', 'rounds_estimated', 'group_membership_role']);
        $isAdmin = $registration->event_group_id === $eventGroup->id
            && $registration->group_membership_status === GroupMembershipStatus::Accepted
            && $registration->group_membership_role === GroupMembershipRole::Admin;

        $pending = $isAdmin && ! $eventGroup->donationEvent->hasEnded()
            ? $eventGroup->athleteRegistrations()->where('group_membership_status', GroupMembershipStatus::Pending->value)->with(['externalUser' => fn ($query) => $query->select(['id', 'first_name', 'last_name', 'public_id'])])->get(['id', 'external_user_id', 'event_group_id'])
            : collect();

        return view('pages.portal.event-group', compact('eventGroup', 'registration', 'accepted', 'pending', 'isAdmin') + $viewData);
    }
}
