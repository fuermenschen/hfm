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
            <flux:card id="participation-{{ $registration['id'] }}" class="space-y-6 rounded-xl border-hfm-light/40 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
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

                 @if ($registration['verified'])
                 <div class="flex flex-wrap gap-3">
                     <flux:button href="{{ $registration['welcomeLetterUrl'] }}" variant="outline" icon="document-arrow-down">
                         Willkommensbrief herunterladen
                     </flux:button>
                 </div>

                 <section class="space-y-3" aria-labelledby="story-images-{{ $registration['id'] }}">
                    <flux:callout icon="megaphone" color="green" class="rounded-2xl" inline>
                         <flux:callout.heading id="story-images-{{ $registration['id'] }}">Deine Runden können noch mehr bewegen</flux:callout.heading>
                         <flux:callout.text>Gewinne weitere Spender:innen für deine Spendenaktion: Teile personalisierte Bilder und Texte.</flux:callout.text>
                        <x-slot name="actions">
                            <flux:modal.trigger name="share-story-{{ $registration['id'] }}" data-story-share-open="share-story-{{ $registration['id'] }}">
                                <flux:button variant="outline" icon="arrow-up-tray">Story teilen</flux:button>
                            </flux:modal.trigger>
                        </x-slot>
                    </flux:callout>

                     <x-portal.story-share
                         :registration-id="$registration['id']"
                         :athlete-name="null"
                         :share-texts="$registration['shareTexts']"
                         show-text-tab
                     />
                </section>
                @endif

                <dl class="grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-hfm-light/15 p-3 dark:bg-slate-800">
                        <dt class="text-sm text-hfm-dark dark:text-hfm-light">Geschätzte Runden</dt>
                        <dd class="mt-1 text-xl font-semibold tabular-nums">{{ $registration['roundsEstimated'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-950/40">
                        <dt class="text-sm text-emerald-700 dark:text-emerald-300">Absolvierte Runden</dt>
                        <dd class="mt-1 text-xl font-semibold tabular-nums">{{ $registration['roundsDone'] }}</dd>
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
                        <flux:text data-expandable-comment class="mt-1 line-clamp-3">{{ $registration['comment'] }}</flux:text>
                        <flux:button data-expand-comment variant="ghost" size="sm" class="mt-1" hidden>
                            Gesamten Kommentar anzeigen
                        </flux:button>
                    </div>
                @endif

                <flux:separator variant="subtle" />

                @if ($registration['donationCount'] > 0)
                    <flux:accordion>
                        <flux:accordion.item>
                            <flux:accordion.heading>
                                Spender:innen ({{ $registration['donationCount'] }}) · Fr. {{ number_format($registration['estimatedDonationAmount'], 2, '.', "'") }} erwartet{{ $registration['pendingDonationCount'] > 0 ? ' · '.$registration['pendingDonationCount'].' offen' : '' }}
                            </flux:accordion.heading>
                            <flux:accordion.content class="space-y-4">
                                @foreach ($registration['donations'] as $donation)
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
                                @endforeach
                            </flux:accordion.content>
                        </flux:accordion.item>
                    </flux:accordion>
                @else
                    <div class="space-y-1">
                        <flux:heading level="3">Spender:innen</flux:heading>
                        <flux:text>Noch keine Spenden für diese Teilnahme.</flux:text>
                    </div>
                @endif
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
