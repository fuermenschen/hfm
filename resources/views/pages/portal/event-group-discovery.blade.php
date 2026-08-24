@extends('layouts.portal')

@section('title', 'Gruppe finden')

@section('content')
    <div class="space-y-8">
        <header class="space-y-2">
            <flux:button
                href="{{ route('portal.participations', ['anlass' => $registration->donationEvent->slug]) }}"
                variant="ghost"
                icon="arrow-left"
                wire:navigate
            >Zur Teilnahme</flux:button>
            <flux:heading size="xl" level="1">Gruppe finden</flux:heading>
            <flux:text>Gruppen für {{ $registration->donationEvent->title }}.</flux:text>
        </header>

        <x-portal.success-message />

        @if ($eventEnded)
            <flux:callout icon="clock" variant="warning"
                ><flux:callout.heading>Dieser Anlass ist beendet</flux:callout.heading
                ><flux:callout.text>Gruppen sind nur noch als Archiv verfügbar.</flux:callout.text></flux:callout>
        @elseif (! $registration->hasGroupMembership())
            <flux:card class="border-hfm-light/40 space-y-4 rounded-xl bg-white dark:border-slate-700 dark:bg-slate-900">
                <flux:heading level="2">Eigene Gruppe gründen</flux:heading>
                <livewire:portal-event-group-actions
                    :registration-id="$registration->id"
                    action="create"
                    :wire:key="'group-create-'.$registration->id"
                />
            </flux:card>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            @forelse ($groups as $group)
                <flux:card class="border-hfm-light/40 space-y-4 rounded-xl bg-white dark:border-slate-700 dark:bg-slate-900">
                    <div>
                        <flux:heading size="lg" level="2">{{ $group['name'] }}</flux:heading
                        ><flux:text class="mt-1">{{ $group['acceptedCount'] }} bestätigte Mitglieder</flux:text>
                    </div>
                    @if (! $eventEnded && ! $registration->hasGroupMembership())
                        <livewire:portal-event-group-actions
                            :registration-id="$registration->id"
                            action="request"
                            :group-id="$group['id']"
                            :wire:key="'group-request-'.$registration->id.'-'.$group['id']"
                        />
                    @endif
                </flux:card>
            @empty
                <flux:card class="text-center"
                    ><flux:heading level="2">Noch keine Gruppe</flux:heading
                    ><flux:text class="mt-2">Gründe die erste Gruppe für diesen Anlass.</flux:text></flux:card>
            @endforelse
        </div>
    </div>
@endsection
