@extends('layouts.portal')

@section('title', $eventGroup->name)

@section('content')
    <div class="space-y-8">
        <header class="space-y-3">
            <flux:button
                href="{{ route('portal.participations', ['anlass' => $eventGroup->donationEvent->slug]) }}"
                variant="ghost"
                icon="arrow-left"
                wire:navigate
            >Zu Teilnahmen</flux:button>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <flux:heading size="xl" level="1">{{ $eventGroup->name }}</flux:heading>
                    <flux:text class="mt-2"
                        >{{ $eventGroup->donationEvent->title }} · {{ $accepted->count() }} bestätigte
                        Mitglieder</flux:text>
                </div>
                <flux:badge :color="$isAdmin ? 'green' : 'zinc'">{{ $isAdmin ? 'Administrator:in' : 'Mitgliedschaft anzeigen' }}</flux:badge>
            </div>
        </header>

        <x-portal.success-message />

        @if ($eventGroup->donationEvent->hasEnded())
            <flux:callout icon="archive-box" variant="secondary">
                <flux:callout.heading>Archiv</flux:callout.heading>
                <flux:callout.text>
                    Der Anlass ist beendet. Gruppen können nicht mehr geändert werden.</flux:callout.text>
            </flux:callout>
        @elseif ($registration->event_group_id === $eventGroup->id && $registration->group_membership_status->value === 'pending')
            <flux:callout icon="clock" variant="warning">
                <flux:callout.heading>Deine Anfrage ist offen</flux:callout.heading>
                <x-slot name="actions">
                    <livewire:portal-event-group-actions
                        :registration-id="$registration->id"
                        action="withdraw"
                        :group-id="$eventGroup->id"
                        :group-name="$eventGroup->name"
                        :wire:key="'group-withdraw-'.$registration->id"
                    />
                </x-slot>
            </flux:callout>
        @elseif ($registration->event_group_id === $eventGroup->id && $registration->group_membership_status->value === 'accepted' && ($registration->group_membership_role->value !== 'admin' || $accepted->where('group_membership_role', \App\Enums\GroupMembershipRole::Admin)->count() > 1))
            <livewire:portal-event-group-actions
                :registration-id="$registration->id"
                action="leave"
                :group-id="$eventGroup->id"
                :group-name="$eventGroup->name"
                :wire:key="'group-leave-'.$registration->id"
            />
        @elseif ($registration->event_group_id === $eventGroup->id && $registration->group_membership_status->value === 'accepted' && $registration->group_membership_role->value === 'admin')
            <flux:callout icon="information-circle" variant="secondary">
                <flux:callout.text>
                    Du bist letzte:r Administrator:in. Befördere zuerst ein Mitglied, bevor du die Gruppe verlassen
                    kannst.</flux:callout.text>
            </flux:callout>
        @endif

        @if ($registration->event_group_id === $eventGroup->id && $registration->group_membership_status->value === 'accepted')
            <section class="space-y-4">
                <flux:heading size="lg" level="2">Mitglieder</flux:heading>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($accepted as $member)
                        <flux:card class="border-hfm-light/40 flex flex-col gap-3 rounded-xl bg-white dark:border-slate-700 dark:bg-slate-900">
                            <div>
                                <flux:heading level="3">{{ $member->externalUser->privacy_name }} ({{ $member->externalUser->public_id_string }})</flux:heading>
                                @if ($member->group_membership_role->value === 'admin')
                                    <flux:badge class="mt-2" color="green">Administrator:in</flux:badge>
                                @endif
                            </div>
                            <dl class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Sportart</dt>
                                    <dd class="mt-1 font-medium">{{ $member->sportType->name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Geschätzte Runden</dt>
                                    <dd class="mt-1 font-medium tabular-nums">{{ $member->rounds_estimated }}</dd>
                                </div>
                            </dl>
                            @if ($isAdmin && ! $eventGroup->donationEvent->hasEnded() && ! $member->is($registration))
                                <div class="flex flex-wrap gap-2">
                                    @if ($member->group_membership_role->value === 'member')
                                        <livewire:portal-event-group-actions
                                            :registration-id="$registration->id"
                                            action="promote"
                                            :group-id="$eventGroup->id"
                                            :target-registration-id="$member->id"
                                            :group-name="$eventGroup->name"
                                            :target-name="$member->externalUser->privacy_name"
                                            :wire:key="'group-promote-'.$member->id"
                                        />
                                    @else
                                        <livewire:portal-event-group-actions
                                            :registration-id="$registration->id"
                                            action="demote"
                                            :group-id="$eventGroup->id"
                                            :target-registration-id="$member->id"
                                            :group-name="$eventGroup->name"
                                            :target-name="$member->externalUser->privacy_name"
                                            :wire:key="'group-demote-'.$member->id"
                                        />
                                    @endif
                                    <livewire:portal-event-group-actions
                                        :registration-id="$registration->id"
                                        action="remove"
                                        :group-id="$eventGroup->id"
                                        :target-registration-id="$member->id"
                                        :group-name="$eventGroup->name"
                                        :target-name="$member->externalUser->privacy_name"
                                        :wire:key="'group-remove-'.$member->id"
                                    />
                                </div>
                            @endif
                        </flux:card>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($isAdmin && ! $eventGroup->donationEvent->hasEnded())
            <section class="space-y-4">
                <flux:heading size="lg" level="2">Offene Anfragen ({{ $pending->count() }})</flux:heading>
                @forelse ($pending as $applicant)
                    <flux:card class="border-hfm-light/40 flex flex-col gap-3 rounded-xl bg-white sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:bg-slate-900">
                        <flux:text>{{ $applicant->externalUser->privacy_name }} ({{ $applicant->externalUser->public_id_string }})</flux:text>
                        <div class="flex gap-2">
                            <livewire:portal-event-group-actions
                                :registration-id="$registration->id"
                                action="accept"
                                :group-id="$eventGroup->id"
                                :target-registration-id="$applicant->id"
                                :group-name="$eventGroup->name"
                                :target-name="$applicant->externalUser->privacy_name"
                                :wire:key="'group-accept-'.$applicant->id"
                            />
                            <livewire:portal-event-group-actions
                                :registration-id="$registration->id"
                                action="deny"
                                :group-id="$eventGroup->id"
                                :target-registration-id="$applicant->id"
                                :group-name="$eventGroup->name"
                                :target-name="$applicant->externalUser->privacy_name"
                                :wire:key="'group-deny-'.$applicant->id"
                            />
                        </div>
                    </flux:card>
                @empty
                    <flux:text>Keine offenen Anfragen.</flux:text>
                @endforelse
            </section>
            @if ($accepted->count() === 1 && $pending->isEmpty())
                <livewire:portal-event-group-actions
                    :registration-id="$registration->id"
                    action="delete"
                    :group-id="$eventGroup->id"
                    :group-name="$eventGroup->name"
                    :wire:key="'group-delete-'.$eventGroup->id"
                />
            @endif
        @endif
    </div>
@endsection
