@extends('layouts.portal')

@section('title', 'Spenden')

@section('content')
    <div class="space-y-8">
        <x-portal.page-header title="Spenden" :events="$events" :selected-event-slug="$selectedEventSlug" />

        <x-portal.success-message />

        @if ($donations->where('verified', false)->isNotEmpty())
            <section class="space-y-3" aria-labelledby="pending-donations">
                <flux:heading id="pending-donations" size="lg" level="2">Aktion erforderlich</flux:heading>
                @foreach ($donations->where('verified', false) as $donation)
                    <flux:callout icon="clock" variant="warning" class="rounded-2xl">
                        <flux:callout.heading>Spende an {{ $donation['athlete'] }} bestätigen</flux:callout.heading>
                        <flux:callout.text>
                            {{ $donation['event'] }}{{ $donation['date'] ? ' · '.$donation['date'] : '' }} · {{ $donation['amountLabel'] }}:
                            Fr. {{ number_format($donation['amount'], 2, '.', "'") }}
                        </flux:callout.text>
                        <x-slot name="actions">
                            <livewire:portal-confirmation-button
                                type="donation"
                                :record-id="$donation['id']"
                                :wire:key="'registration-donation-'.$donation['id']"
                            />
                        </x-slot>
                    </flux:callout>
                @endforeach
            </section>
        @endif

        @if ($donations->where('verified', true)->isNotEmpty())
            @foreach ($donations->where('verified', true)->groupBy('eventId') as $eventDonations)
                <section class="space-y-3" aria-label="Spenden">
                    @if ($selectedEventSlug === null)
                        <div>
                            <flux:heading
                                id="donation-event-{{ $eventDonations->first()['eventId'] }}"
                                size="lg"
                                level="2"
                            >{{ $eventDonations->first()['event'] }}</flux:heading>
                            @if ($eventDonations->first()['date'])
                                <flux:text>{{ $eventDonations->first()['date'] }}</flux:text>
                            @endif
                        </div>
                    @endif

                    <div class="border-hfm-light/40 divide-hfm-light/40 overflow-hidden rounded-xl border bg-white shadow-sm dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-900">
                        @foreach ($eventDonations as $donation)
                            <details class="group">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-4 marker:content-none">
                                    <div class="min-w-0">
                                        <flux:heading level="3">{{ $donation['athlete'] }}</flux:heading>
                                        <flux:text class="mt-1">{{ $donation['amountLabel'] }}</flux:text>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2 text-right">
                                        <span class="font-semibold tabular-nums">Fr. {{ number_format($donation['amount'], 2, '.', "'") }}</span>
                                        <flux:icon.chevron-down class="size-5 transition-transform group-open:rotate-180" />
                                    </div>
                                </summary>

                                <div class="border-hfm-light/40 space-y-6 border-t p-4 dark:border-slate-700">
                                    <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                        <div>
                                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">Sportart</dt>
                                            <dd class="mt-1 font-medium">{{ $donation['sport'] }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">Begünstigte</dt>
                                            <dd class="mt-1 font-medium">{{ $donation['partner'] }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">Pro Runde</dt>
                                            <dd class="mt-1 font-medium tabular-nums">
                                                Fr. {{ number_format($donation['amountPerRound'], 2, '.', "'") }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">Minimum / Maximum</dt>
                                            <dd class="mt-1 font-medium tabular-nums">
                                                {{ $donation['amountMin'] !== null ? 'Fr. '.number_format($donation['amountMin'], 2, '.', "'") : '–' }} / {{ $donation['amountMax'] !== null ? 'Fr. '.number_format($donation['amountMax'], 2, '.', "'") : '–' }}
                                            </dd>
                                        </div>
                                    </dl>

                                    @if ($donation['comment'])
                                        <div>
                                            <flux:heading size="sm" level="4">Dein Kommentar</flux:heading>
                                            <flux:text class="mt-1">{{ $donation['comment'] }}</flux:text>
                                        </div>
                                    @endif

                                    @if ($donation['athleteVerified'])
                                        <div>
                                            <flux:modal.trigger
                                                name="share-story-donor-{{ $donation['id'] }}"
                                                data-story-share-open="share-story-donor-{{ $donation['id'] }}"
                                            >
                                                <flux:button variant="outline" icon="arrow-up-tray"
                                                    >Story teilen</flux:button>
                                            </flux:modal.trigger>
                                            <x-portal.story-share
                                                :registration-id="$donation['athleteRegistrationId']"
                                                :share-id="'donor-'.$donation['id']"
                                                :athlete-name="$donation['athlete']"
                                                :heading="'Weitere Spender:innen für '.$donation['athlete'].' finden'"
                                                description="Diese personalisierte Story kannst du direkt weitergeben."
                                            />
                                        </div>
                                    @endif
                                </div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @endforeach
        @endif

        @if ($donations->isEmpty())
            <flux:card class="border-hfm-light/40 rounded-xl bg-white text-center shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="bg-hfm-light/25 text-hfm-dark dark:text-hfm-light mx-auto flex size-12 items-center justify-center rounded-full dark:bg-slate-800">
                    <flux:icon.heart class="size-6" />
                </div>
                <flux:heading size="lg" level="2" class="mt-4">Noch keine Spende</flux:heading>
                <flux:text class="mt-2">Für den gewählten Anlass ist keine Spende vorhanden.</flux:text>
                @if ($donorRegistrationOpen)
                    <flux:button
                        href="{{ route('become-donor') }}"
                        wire:navigate
                        icon="heart"
                        variant="primary"
                        class="mt-5"
                    >Beim aktuellen Anlass spenden</flux:button>
                @endif
            </flux:card>
        @endif
    </div>
@endsection
