@extends('layouts.portal')

@section('title', 'Teilnahmen')

@section('content')
    <div class="space-y-8">
        <x-portal.page-header
            title="Teilnahmen"
            subtitle="Deine Teilnahmen und die dazugehörigen Spenden."
            :events="$events"
            :selected-event-slug="$selectedEventSlug"
        />

        <x-portal.success-message />

        @forelse ($registrations as $registration)
            <flux:card class="space-y-6 rounded-2xl border-sky-100 bg-white/90 shadow-sm dark:border-slate-700 dark:bg-slate-900/90">
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
                    <flux:callout icon="clock" variant="warning" class="rounded-2xl">
                        <flux:callout.heading>Noch 1 Schritt: Teilnahme bestätigen</flux:callout.heading>
                        <x-slot name="actions">
                            <livewire:portal-confirmation-button type="athlete" :record-id="$registration['id']" :wire:key="'registration-athlete-'.$registration['id']" />
                        </x-slot>
                    </flux:callout>
                @endunless

                <dl class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-sky-50 p-4 dark:bg-sky-950/40">
                        <dt class="text-sm text-sky-700 dark:text-sky-300">Geschätzte Runden</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ $registration['roundsEstimated'] }}</dd>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 p-4 dark:bg-emerald-950/40">
                        <dt class="text-sm text-emerald-700 dark:text-emerald-300">Absolvierte Runden</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ $registration['roundsDone'] }}</dd>
                    </div>
                </dl>

                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Sportart</dt>
                        <dd class="mt-1 font-medium">{{ $registration['sport'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">Begünstigte</dt>
                        <dd class="mt-1 font-medium">{{ $registration['partner'] }}</dd>
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
                        <flux:card class="space-y-4 rounded-2xl border-sky-100 bg-sky-50/60 shadow-none dark:border-slate-700 dark:bg-slate-800/70">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <flux:heading level="4">{{ $donation['donor'] }}</flux:heading>
                                <flux:badge :color="$donation['verified'] ? 'green' : 'amber'">
                                    {{ $donation['verified'] ? 'Bestätigt' : 'Ausstehend' }}
                                </flux:badge>
                            </div>

                            <dl class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-xl bg-white p-3 dark:bg-slate-900/70">
                                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Erwarteter Betrag</dt>
                                    <dd class="mt-1 text-xl font-semibold tabular-nums"><span class="text-sm font-medium">Fr.</span> {{ number_format($donation['estimatedAmount'], 2, '.', "'") }}</dd>
                                </div>
                                <div class="rounded-xl bg-white p-3 dark:bg-slate-900/70">
                                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Effektiver Betrag</dt>
                                    @if ($registration['roundsDone'] > 0)
                                        <dd class="mt-1 text-xl font-semibold tabular-nums"><span class="text-sm font-medium">Fr.</span> {{ number_format($donation['currentAmount'], 2, '.', "'") }}</dd>
                                    @else
                                        <dd class="mt-1 font-medium text-zinc-500 dark:text-zinc-400">Noch nicht final</dd>
                                    @endif
                                </div>
                                <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Pro Runde</dt><dd class="tabular-nums">Fr. {{ number_format($donation['amountPerRound'], 2, '.', "'") }}</dd></div>
                                <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Minimum / Maximum</dt><dd class="tabular-nums">{{ $donation['amountMin'] !== null ? 'Fr. '.number_format($donation['amountMin'], 2, '.', "'") : '–' }} / {{ $donation['amountMax'] !== null ? 'Fr. '.number_format($donation['amountMax'], 2, '.', "'") : '–' }}</dd></div>
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
            <flux:card class="rounded-2xl border-sky-200 bg-gradient-to-br from-sky-50 to-white text-center shadow-sm dark:border-sky-900/70 dark:from-sky-950/30 dark:to-slate-900">
                <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-200"><flux:icon.trophy class="size-6" /></div>
                <flux:heading size="lg" level="2" class="mt-4">Noch keine Teilnahme</flux:heading>
                <flux:text class="mt-2">Für den gewählten Anlass ist keine Teilnahme vorhanden.</flux:text>
                @if ($athleteRegistrationOpen)
                    <flux:button href="{{ route('become-athlete') }}" wire:navigate icon="trophy" class="mt-5">Beim aktuellen Anlass anmelden</flux:button>
                @endif
            </flux:card>
        @endforelse
    </div>
@endsection
