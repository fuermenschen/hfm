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
            <flux:card class="space-y-6 rounded-xl border-hfm-light/40 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
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
                    <div class="rounded-xl bg-hfm-light/15 p-4 dark:bg-slate-800">
                        <dt class="text-sm text-hfm-dark dark:text-hfm-light">Geschätzte Runden</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ $registration['roundsEstimated'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-4 dark:bg-emerald-950/40">
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

                <section class="space-y-3" aria-labelledby="story-images-{{ $registration['id'] }}">
                    <div>
                        <flux:heading id="story-images-{{ $registration['id'] }}" size="sm" level="3">Bilder für Social Media</flux:heading>
                        <flux:text class="mt-1">Personalisiertes Bild für Instagram- oder WhatsApp-Story herunterladen.</flux:text>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <flux:button
                            href="{{ route('portal.story-image.download', ['athleteRegistration' => $registration['id'], 'variant' => 'light']) }}"
                            icon="arrow-down-tray"
                            variant="outline"
                        >
                            Helles Bild
                        </flux:button>
                        <flux:button
                            href="{{ route('portal.story-image.download', ['athleteRegistration' => $registration['id'], 'variant' => 'dark']) }}"
                            icon="arrow-down-tray"
                            variant="outline"
                        >
                            Dunkles Bild
                        </flux:button>
                    </div>
                </section>

                <flux:separator variant="subtle" />

                <div class="space-y-4">
                    <flux:heading level="3">Spender:innen</flux:heading>

                    @forelse ($registration['donations'] as $donation)
                        <flux:card class="space-y-4 rounded-xl border-hfm-light/40 bg-hfm-light/10 shadow-none dark:border-slate-700 dark:bg-slate-800">
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
            <flux:card class="rounded-xl border-hfm-light/40 bg-white text-center shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-hfm-light/25 text-hfm-dark dark:bg-slate-800 dark:text-hfm-light"><flux:icon.trophy class="size-6" /></div>
                <flux:heading size="lg" level="2" class="mt-4">Noch keine Teilnahme</flux:heading>
                <flux:text class="mt-2">Für den gewählten Anlass ist keine Teilnahme vorhanden.</flux:text>
                @if ($athleteRegistrationOpen)
                    <flux:button href="{{ route('become-athlete') }}" wire:navigate icon="trophy" class="mt-5">Beim aktuellen Anlass anmelden</flux:button>
                @endif
            </flux:card>
        @endforelse
    </div>
@endsection
