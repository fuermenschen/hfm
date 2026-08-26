@extends('layouts.portal')

@section('title', 'Teilnahmen')

@section('content')
    <div class="space-y-8">
        <x-portal.page-header
            title="Teilnahmen"
            :events="$events"
            :selected-event-slug="$selectedEventSlug"
            :show-event="false"
        />

        <x-portal.success-message />

        @if ($registrations->where('verified', false)->isNotEmpty())
            <section class="space-y-3" aria-labelledby="pending-participations">
                <flux:heading id="pending-participations" size="lg" level="2">Aktion erforderlich</flux:heading>
                @foreach ($registrations->where('verified', false) as $registration)
                    <flux:callout icon="clock" variant="warning" class="rounded-2xl">
                        <flux:callout.heading>{{ $registration['event'] }}: Teilnahme bestätigen</flux:callout.heading>
                        <flux:callout.text>
                            {{ $registration['sport'] }} · {{ $registration['roundsEstimated'] }} geschätzte Runden · {{ $registration['partner'] }}
                        </flux:callout.text>
                        <x-slot name="actions">
                            <livewire:portal-confirmation-button
                                type="athlete"
                                :record-id="$registration['id']"
                                :wire:key="'registration-athlete-'.$registration['id']"
                            />
                        </x-slot>
                    </flux:callout>
                @endforeach
            </section>
        @endif

        @if ($registrations->where('verified', true)->isNotEmpty())
            @foreach ($registrations->where('verified', true)->groupBy('eventId') as $eventRegistrations)
                <section class="space-y-3" aria-label="Teilnahmen">
                    <div class="space-y-4">
                        @foreach ($eventRegistrations as $registration)
                            <flux:card
                                id="participation-{{ $registration['id'] }}"
                                class="border-hfm-light/40 space-y-6 rounded-xl bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
                            >
                                <div>
                                    <flux:heading size="lg" level="2">{{ $registration['event'] }}</flux:heading>
                                    @if ($registration['date'])
                                        <flux:text class="mt-1">{{ $registration['date'] }}</flux:text>
                                    @endif
                                </div>

                                <div class="space-y-6">
                                    <flux:separator variant="subtle" />

                                    <dl>
                                        <div>
                                            <dt class="text-hfm-dark dark:text-hfm-light text-sm">
                                                {{ $registration['eventStarted'] ? 'Spenden (tatsächlich, nur bestätigt)' : 'Spenden (geschätzt, nur bestätigt)' }}
                                            </dt>
                                            <dd class="mt-1 text-2xl font-semibold tabular-nums">
                                                <span class="text-base font-medium">Fr.</span>
                                                {{ number_format($registration['confirmedDonationAmount'], 2, '.', "'") }}
                                            </dd>
                                        </div>
                                    </dl>

                                    @if ($registration['verified'])
                                        <section
                                            class="space-y-3"
                                            aria-labelledby="story-images-{{ $registration['id'] }}"
                                        >
                                            <flux:callout icon="megaphone" color="green" class="rounded-2xl" inline>
                                                <flux:callout.heading id="story-images-{{ $registration['id'] }}">
                                                    Deine Runden können noch mehr bewegen</flux:callout.heading>
                                                <flux:callout.text>
                                                    Gewinne weitere Spender:innen für deine Spendenaktion: Teile
                                                    personalisierte Bilder und Texte.</flux:callout.text>
                                                <x-slot name="actions">
                                                    <flux:modal.trigger
                                                        name="share-story-{{ $registration['id'] }}"
                                                        data-story-share-open="share-story-{{ $registration['id'] }}"
                                                    >
                                                        <flux:button variant="primary" icon="arrow-up-tray"
                                                            >Story teilen</flux:button>
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

                                        <flux:accordion>
                                            <flux:accordion.item>
                                                <flux:accordion.heading>Runden</flux:accordion.heading>
                                                <flux:accordion.content>
                                                    <dl class="grid grid-cols-2 gap-2">
                                                        <div class="p-3">
                                                            <dt class="text-hfm-dark dark:text-hfm-light text-sm">
                                                                Geschätzte Runden
                                                            </dt>
                                                            <dd class="mt-1 text-xl font-semibold tabular-nums">
                                                                {{ $registration['roundsEstimated'] }}
                                                            </dd>
                                                        </div>
                                                        <div class="p-3">
                                                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">
                                                                Absolvierte Runden
                                                            </dt>
                                                            <dd class="mt-1 text-xl font-semibold tabular-nums">
                                                                {{ $registration['roundsDone'] }}
                                                            </dd>
                                                        </div>
                                                    </dl>
                                                </flux:accordion.content>
                                            </flux:accordion.item>
                                            <flux:accordion.item>
                                                <flux:accordion.heading>Gruppe</flux:accordion.heading>
                                                <flux:accordion.content class="space-y-3">
                                                    @if ($registration['eventEnded'])
                                                        @if ($registration['group'] !== null && $registration['group']['status'] === 'accepted')
                                                            <flux:callout
                                                                icon="archive-box"
                                                                variant="secondary"
                                                                inline
                                                                class="border-0 bg-transparent shadow-none dark:bg-transparent"
                                                            >
                                                                <flux:callout.heading>
                                                                    {{ $registration['group']['name'] }} · {{ $registration['group']['acceptedCount'] }} bestätigte
                                                                    Mitglieder
                                                                </flux:callout.heading>
                                                                <flux:callout.text>
                                                                    Dieser Anlass ist beendet. Die Gruppe ist nur noch
                                                                    als Archiv verfügbar.</flux:callout.text>
                                                                <x-slot name="actions">
                                                                    <flux:button
                                                                        href="{{ route('portal.event-groups.show', $registration['group']['id']) }}"
                                                                        variant="outline"
                                                                        size="sm"
                                                                        wire:navigate
                                                                    >Archiv öffnen</flux:button>
                                                                </x-slot>
                                                            </flux:callout>
                                                        @else
                                                            <flux:callout
                                                                icon="archive-box"
                                                                variant="secondary"
                                                                inline
                                                                class="border-0 bg-transparent shadow-none dark:bg-transparent"
                                                                ><flux:callout.text>
                                                                    Dieser Anlass ist beendet. Gruppen sind nur noch als
                                                                    Archiv verfügbar.</flux:callout.text
                                                                ></flux:callout>
                                                        @endif
                                                    @elseif ($registration['group'] === null)
                                                        <div class="flex flex-wrap gap-3">
                                                            <flux:button
                                                                href="{{ $registration['groupDiscoveryUrl'] }}"
                                                                variant="outline"
                                                                icon="user-group"
                                                                wire:navigate
                                                            >Gruppe finden oder gründen</flux:button>
                                                        </div>
                                                    @elseif ($registration['group']['status'] === 'pending')
                                                        <flux:callout icon="clock" variant="warning" inline
                                                            ><flux:callout.heading>
                                                                {{ $registration['group']['name'] }} · Anfrage
                                                                offen</flux:callout.heading>
                                                            <x-slot name="actions">
                                                                <livewire:portal-event-group-actions
                                                                    :registration-id="$registration['id']"
                                                                    action="withdraw"
                                                                    :group-id="$registration['group']['id']"
                                                                    :group-name="$registration['group']['name']"
                                                                    :wire:key="'participation-group-withdraw-'.$registration['id']"
                                                                /></x-slot
                                                        ></flux:callout>
                                                    @else
                                                        <flux:callout
                                                            icon="user-group"
                                                            variant="secondary"
                                                            inline
                                                            class="border-0 bg-transparent shadow-none dark:bg-transparent"
                                                            ><flux:callout.heading>
                                                                {{ $registration['group']['name'] }} · {{ $registration['group']['acceptedCount'] }} bestätigte
                                                                Mitglieder{{ $registration['group']['role'] === 'admin' ? ' · '.$registration['group']['pendingCount'].' offene Anfragen' : '' }}</flux:callout.heading>
                                                            <x-slot name="actions">
                                                                <div class="flex flex-wrap gap-2">
                                                                    <flux:button
                                                                        href="{{ route('portal.event-groups.show', $registration['group']['id']) }}"
                                                                        variant="outline"
                                                                        size="sm"
                                                                        wire:navigate
                                                                    >{{ $registration['group']['role'] === 'admin' ? 'Gruppe verwalten' : 'Gruppe öffnen' }}</flux:button>
                                                                    @if ($registration['group']['role'] !== 'admin')
                                                                        <livewire:portal-event-group-actions
                                                                            :registration-id="$registration['id']"
                                                                            action="leave"
                                                                            :group-id="$registration['group']['id']"
                                                                            :group-name="$registration['group']['name']"
                                                                            :wire:key="'participation-group-leave-'.$registration['id']"
                                                                        />
                                                                    @endif
                                                                </div></x-slot
                                                        ></flux:callout>
                                                    @endif
                                                </flux:accordion.content>
                                            </flux:accordion.item>
                                    @endif

                                    <flux:accordion.item>
                                        <flux:accordion.heading>Weitere Details</flux:accordion.heading>
                                        <flux:accordion.content class="space-y-4">
                                            <dl class="grid gap-4 sm:grid-cols-2">
                                                <div>
                                                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">Sportart</dt>
                                                    <dd class="mt-1 font-medium">{{ $registration['sport'] }}</dd>
                                                </div>
                                                <div>
                                                    <dt class="text-sm text-zinc-500 dark:text-zinc-400">
                                                        Begünstigte
                                                    </dt>
                                                    <dd class="mt-1 font-medium">{{ $registration['partner'] }}</dd>
                                                </div>
                                            </dl>

                                            @if ($registration['comment'])
                                                <div>
                                                    <flux:heading size="sm" level="3">Dein Kommentar</flux:heading>
                                                    <flux:text
                                                        data-expandable-comment
                                                        class="mt-1 line-clamp-3"
                                                    >{{ $registration['comment'] }}</flux:text>
                                                    <flux:button
                                                        data-expand-comment
                                                        variant="ghost"
                                                        size="sm"
                                                        class="mt-1"
                                                        hidden
                                                    >
                                                        Gesamten Kommentar anzeigen
                                                    </flux:button>
                                                </div>
                                            @endif
                                        </flux:accordion.content>
                                    </flux:accordion.item>

                                    @if ($registration['donationCount'] > 0)
                                        <flux:accordion.item>
                                            <flux:accordion.heading>
                                                Spender:innen ({{ $registration['donationCount'] }})
                                            </flux:accordion.heading>
                                            <flux:accordion.content class="divide-hfm-light/40 divide-y dark:divide-slate-700">
                                                @foreach ($registration['donations'] as $donation)
                                                    <div class="space-y-4 py-4 last:pb-0">
                                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                            <flux:heading level="4">{{ $donation['donor'] }}</flux:heading>
                                                            @unless ($donation['verified'])
                                                                <flux:badge color="amber">Ausstehend</flux:badge>
                                                            @endunless
                                                        </div>

                                                        <dl class="grid gap-4 sm:grid-cols-3">
                                                            <div>
                                                                <dt class="text-sm text-zinc-500 dark:text-zinc-400">
                                                                    {{ $registration['eventStarted'] ? 'Tatsächlicher Betrag' : 'Erwarteter Betrag' }}
                                                                </dt>
                                                                <dd class="mt-1 text-xl font-semibold tabular-nums">
                                                                    <span class="text-sm font-medium">Fr.</span>
                                                                    {{ number_format($donation['amount'], 2, '.', "'") }}
                                                                </dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-sm text-zinc-500 dark:text-zinc-400">
                                                                    Pro Runde
                                                                </dt>
                                                                <dd class="tabular-nums">
                                                                    Fr. {{ number_format($donation['amountPerRound'], 2, '.', "'") }}
                                                                </dd>
                                                            </div>
                                                            <div>
                                                                <dt class="text-sm text-zinc-500 dark:text-zinc-400">
                                                                    Minimum / Maximum
                                                                </dt>
                                                                <dd class="tabular-nums">
                                                                    {{ $donation['amountMin'] !== null ? 'Fr. '.number_format($donation['amountMin'], 2, '.', "'") : '–' }} / {{ $donation['amountMax'] !== null ? 'Fr. '.number_format($donation['amountMax'], 2, '.', "'") : '–' }}
                                                                </dd>
                                                            </div>
                                                        </dl>

                                                        @if ($donation['comment'])
                                                            <flux:text>«{{ $donation['comment'] }}»</flux:text>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </flux:accordion.content>
                                        </flux:accordion.item>
                                    @else
                                        <flux:accordion.item>
                                            <flux:accordion.heading>Spender:innen</flux:accordion.heading>
                                            <flux:accordion.content>
                                                <flux:text>Noch keine Spenden für diese Teilnahme.</flux:text>
                                            </flux:accordion.content>
                                        </flux:accordion.item>
                                    @endif

                                    <flux:accordion.item>
                                        <flux:accordion.heading>Dokumente</flux:accordion.heading>
                                        <flux:accordion.content>
                                            <flux:button
                                                href="{{ $registration['welcomeLetterUrl'] }}"
                                                variant="outline"
                                                icon="document-arrow-down"
                                            >
                                                Willkommensbrief herunterladen
                                            </flux:button>
                                        </flux:accordion.content>
                                    </flux:accordion.item>
                                    </flux:accordion>
                                </div>
                            </flux:card>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif

        @if ($registrations->isEmpty())
            <flux:card class="border-hfm-light/40 rounded-xl bg-white text-center shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="bg-hfm-light/25 text-hfm-dark dark:text-hfm-light mx-auto flex size-12 items-center justify-center rounded-full dark:bg-slate-800">
                    <flux:icon.trophy class="size-6" />
                </div>
                <flux:heading size="lg" level="2" class="mt-4">Noch keine Teilnahme</flux:heading>
                <flux:text class="mt-2">Für den gewählten Anlass ist keine Teilnahme vorhanden.</flux:text>
                @if ($athleteRegistrationOpen)
                    <flux:button href="{{ route('become-athlete') }}" wire:navigate icon="trophy" class="mt-5"
                        >Beim aktuellen Anlass anmelden</flux:button>
                @endif
            </flux:card>
        @endif
    </div>
@endsection
