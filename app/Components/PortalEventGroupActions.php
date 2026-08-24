<?php

declare(strict_types=1);

namespace App\Components;

use App\Actions\AcceptEventGroupMembershipRequestAction;
use App\Actions\CreateEventGroupAction;
use App\Actions\DeleteEventGroupAction;
use App\Actions\DemoteEventGroupAdminAction;
use App\Actions\DenyEventGroupMembershipRequestAction;
use App\Actions\LeaveEventGroupAction;
use App\Actions\PromoteEventGroupMemberAction;
use App\Actions\RemoveEventGroupMemberAction;
use App\Actions\RequestEventGroupMembershipAction;
use App\Actions\WithdrawEventGroupMembershipRequestAction;
use App\Models\AthleteRegistration;
use App\Models\EventGroup;
use App\Models\ExternalUser;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PortalEventGroupActions extends Component
{
    #[Locked]
    public int $registrationId;

    #[Locked]
    public ?int $groupId = null;

    #[Locked]
    public string $action;

    #[Locked]
    public ?int $targetRegistrationId = null;

    public string $name = '';

    public bool $confirming = false;

    public function mount(int $registrationId, string $action, ?int $groupId = null, ?int $targetRegistrationId = null): void
    {
        abort_unless(in_array($action, ['create', 'request', 'withdraw', 'leave', 'accept', 'deny', 'remove', 'promote', 'demote', 'delete'], true), 404);

        $this->registrationId = $registrationId;
        $this->groupId = $groupId;
        $this->action = $action;
        $this->targetRegistrationId = $targetRegistrationId;
    }

    public function submit(
        CreateEventGroupAction $create,
        RequestEventGroupMembershipAction $request,
        WithdrawEventGroupMembershipRequestAction $withdraw,
        LeaveEventGroupAction $leave,
        AcceptEventGroupMembershipRequestAction $accept,
        DenyEventGroupMembershipRequestAction $deny,
        RemoveEventGroupMemberAction $remove,
        PromoteEventGroupMemberAction $promote,
        DemoteEventGroupAdminAction $demote,
        DeleteEventGroupAction $delete,
    ): void {
        $externalUser = auth('external')->user();
        throw_if(! $externalUser instanceof ExternalUser, AuthorizationException::class);

        $registration = AthleteRegistration::query()->with('donationEvent')->findOrFail($this->registrationId);
        throw_unless($registration->external_user_id === $externalUser->id, AuthorizationException::class);
        $group = $this->groupId === null ? null : EventGroup::query()->findOrFail($this->groupId);
        $target = $this->targetRegistrationId === null ? null : AthleteRegistration::query()->findOrFail($this->targetRegistrationId);

        if ($this->action === 'create') {
            $this->validate(['name' => ['required', 'string', 'max:255']]);
            $group = $create($registration->donationEvent, $externalUser, $this->name);
        } elseif ($this->action === 'request') {
            $request($this->group($group), $externalUser);
        } elseif ($this->action === 'withdraw') {
            $withdraw($this->group($group), $externalUser);
        } elseif ($this->action === 'leave') {
            $leave($this->group($group), $externalUser);
        } elseif ($this->action === 'accept') {
            $accept($this->group($group), $this->target($target), $externalUser);
        } elseif ($this->action === 'deny') {
            $deny($this->group($group), $this->target($target), $externalUser);
        } elseif ($this->action === 'remove') {
            $remove($this->group($group), $this->target($target), $externalUser);
        } elseif ($this->action === 'promote') {
            $promote($this->group($group), $this->target($target), $externalUser);
        } elseif ($this->action === 'demote') {
            $demote($this->group($group), $this->target($target), $externalUser);
        } else {
            $delete($this->group($group), $externalUser);
            $this->redirectRoute('portal.participations', ['anlass' => $registration->donationEvent->slug], navigate: true);

            return;
        }

        session()->flash('success', $this->successMessage());
        $this->redirectRoute(
            $group instanceof EventGroup ? 'portal.event-groups.show' : 'portal.participations',
            $group instanceof EventGroup ? ['eventGroup' => $group->id] : ['anlass' => $registration->donationEvent->slug],
            navigate: true,
        );
    }

    public function confirm(): void
    {
        $this->confirming = true;
    }

    public function cancel(): void
    {
        $this->confirming = false;
    }

    public function render(): Factory|View
    {
        return view('components.portal-event-group-actions');
    }

    protected function group(?EventGroup $group): EventGroup
    {
        abort_unless($group instanceof EventGroup, 404);

        return $group;
    }

    protected function target(?AthleteRegistration $target): AthleteRegistration
    {
        abort_unless($target instanceof AthleteRegistration, 404);

        return $target;
    }

    protected function successMessage(): string
    {
        return match ($this->action) {
            'create' => 'Deine Gruppe wurde gegründet.', 'request' => 'Deine Beitrittsanfrage wurde gesendet.',
            'withdraw' => 'Deine Beitrittsanfrage wurde zurückgezogen.', 'leave' => 'Du hast die Gruppe verlassen.',
            'accept' => 'Die Anfrage wurde angenommen.', 'deny' => 'Die Anfrage wurde abgelehnt.',
            'remove' => 'Das Mitglied wurde entfernt.', 'promote' => 'Das Mitglied ist jetzt Administrator:in.',
            'demote' => 'Administratorrechte wurden entfernt.', default => 'Die Gruppe wurde gelöscht.',
        };
    }
}
