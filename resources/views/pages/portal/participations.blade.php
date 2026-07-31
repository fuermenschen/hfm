@extends('layouts.portal')

@section('title', 'Teilnahmen')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:heading size="xl" level="1">Teilnahmen</flux:heading>
                <flux:text class="mt-2 text-base">Deine Teilnahmen und die dazugehörigen Spenden.</flux:text>
            </div>

            <x-portal.event-filter :events="$events" :selected-event-slug="$selectedEventSlug" />
        </div>

        <x-portal.success-message />

        @forelse ($registrations as $registration)
            <flux:card class="space-y-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <flux:heading size="lg" level="2">{{ $registration['event'] }}</flux:heading>
                        @if ($registration['date'])
                            <flux:text class="mt-1">{{ $registration['date'] }}</flux:text>
                        @endif
                    </div>

                    <flux:badge :color="$registration['verified'] ? 'green' : 'amber'" icon="{{ $registration['verified'] ? 'check-circle' : 'clock' }}">
                        {{ $registration['verified'] ? 'Bestätigt' : 'Bestätigung ausstehend' }}
                    </flux:badge>
                </div>

                @unless ($registration['verified'])
                    <flux:callout icon="exclamation-triangle" variant="warning">
                        <flux:callout.heading>Teilnahme noch nicht bestätigt</flux:callout.heading>
                        <x-slot name="actions">
                            <livewire:portal-confirmation-button type="athlete" :record-id="$registration['id']" :wire:key="'registration-athlete-'.$registration['id']" />
                        </x-slot>
                    </flux:callout>
                @endunless

                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Sportart</dt>
                        <dd class="mt-1 font-medium">{{ $registration['sport'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Begünstigte</dt>
                        <dd class="mt-1 font-medium">{{ $registration['partner'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Geschätzte Runden</dt>
                        <dd class="mt-1 font-medium">{{ $registration['roundsEstimated'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Absolvierte Runden</dt>
                        <dd class="mt-1 font-medium">{{ $registration['roundsDone'] }}</dd>
                    </div>
                </dl>

                @if ($registration['comment'])
                    <div>
                        <flux:heading size="sm" level="3">Dein Kommentar</flux:heading>
                        <flux:text class="mt-1">{{ $registration['comment'] }}</flux:text>
                    </div>
                @endif

                <flux:separator variant="subtle" />

                <div class="space-y-4">
                    <flux:heading level="3">Spender:innen</flux:heading>

                    @forelse ($registration['donations'] as $donation)
                        <flux:card class="space-y-4 bg-zinc-50 dark:bg-zinc-800">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <flux:heading level="4">{{ $donation['donor'] }}</flux:heading>
                                <flux:badge :color="$donation['verified'] ? 'green' : 'amber'">
                                    {{ $donation['verified'] ? 'Bestätigt' : 'Ausstehend' }}
                                </flux:badge>
                            </div>

                            <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Pro Runde</dt><dd>Fr. {{ number_format($donation['amountPerRound'], 2, '.', "'") }}</dd></div>
                                <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Minimum / Maximum</dt><dd>{{ $donation['amountMin'] !== null ? 'Fr. '.number_format($donation['amountMin'], 2, '.', "'") : '–' }} / {{ $donation['amountMax'] !== null ? 'Fr. '.number_format($donation['amountMax'], 2, '.', "'") : '–' }}</dd></div>
                                <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Erwartet</dt><dd>Fr. {{ number_format($donation['estimatedAmount'], 2, '.', "'") }}</dd></div>
                                <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Aktuell</dt><dd>Fr. {{ number_format($donation['currentAmount'], 2, '.', "'") }}</dd></div>
                            </dl>

                            @if ($donation['comment'])
                                <flux:text>«{{ $donation['comment'] }}»</flux:text>
                            @endif
                        </flux:card>
                    @empty
                        <flux:text>Noch keine Spenden für diese Teilnahme.</flux:text>
                    @endforelse
                </div>
            </flux:card>
        @empty
            <flux:callout icon="information-circle">
                <flux:callout.heading>Keine Teilnahmen gefunden</flux:callout.heading>
                <flux:callout.text>Für den gewählten Anlass sind keine Teilnahmen vorhanden.</flux:callout.text>
            </flux:callout>
        @endforelse
    </div>
@endsection
