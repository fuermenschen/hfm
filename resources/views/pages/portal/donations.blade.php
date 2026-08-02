@extends('layouts.portal')

@section('title', 'Spenden')

@section('content')
    <div class="space-y-8">
        <x-portal.page-header
            title="Spenden"
            subtitle="Deine Spenden an Sportler:innen."
            :events="$events"
            :selected-event-slug="$selectedEventSlug"
        />

        <x-portal.success-message />

        @forelse ($donations as $donation)
            <flux:card class="space-y-6 rounded-xl border-hfm-light/40 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <flux:heading size="lg" level="2">{{ $donation['athlete'] }}</flux:heading>
                        <flux:text class="mt-1">{{ $donation['event'] }}{{ $donation['date'] ? ' · '.$donation['date'] : '' }}</flux:text>
                    </div>

                    <flux:badge :color="$donation['verified'] ? 'green' : 'amber'" icon="{{ $donation['verified'] ? 'check-circle' : 'clock' }}">
                        {{ $donation['verified'] ? 'Bestätigt' : 'Bestätigung ausstehend' }}
                    </flux:badge>
                </div>

                @unless ($donation['verified'])
                    <flux:callout icon="clock" variant="warning" class="rounded-2xl">
                        <flux:callout.heading>Noch 1 Schritt: Spende bestätigen</flux:callout.heading>
                        <x-slot name="actions">
                            <livewire:portal-confirmation-button type="donation" :record-id="$donation['id']" :wire:key="'registration-donation-'.$donation['id']" />
                        </x-slot>
                    </flux:callout>
                @endunless

                <dl class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl bg-hfm-light/15 p-4 dark:bg-slate-800">
                        <dt class="text-sm text-hfm-dark dark:text-hfm-light">Voraussichtlicher eigener Beitrag</dt>
                        <dd class="mt-1 text-2xl font-semibold tabular-nums"><span class="text-base font-medium">Fr.</span> {{ number_format($donation['estimatedAmount'], 2, '.', "'") }}</dd>
                    </div>
                    <div class="rounded-xl bg-emerald-50 p-4 dark:bg-emerald-950/40">
                        <dt class="text-sm text-emerald-700 dark:text-emerald-300">Aktueller eigener Beitrag</dt>
                        @if ($donation['hasCompletedRounds'])
                            <dd class="mt-1 text-2xl font-semibold tabular-nums"><span class="text-base font-medium">Fr.</span> {{ number_format($donation['currentAmount'], 2, '.', "'") }}</dd>
                        @else
                            <dd class="mt-2 font-medium text-zinc-500 dark:text-zinc-400">Noch nicht final</dd>
                        @endif
                    </div>
                </dl>

                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Sportart</dt><dd class="mt-1 font-medium">{{ $donation['sport'] }}</dd></div>
                    <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Begünstigte</dt><dd class="mt-1 font-medium">{{ $donation['partner'] }}</dd></div>
                    <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Pro Runde</dt><dd class="mt-1 font-medium tabular-nums">Fr. {{ number_format($donation['amountPerRound'], 2, '.', "'") }}</dd></div>
                    <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Minimum / Maximum</dt><dd class="mt-1 font-medium tabular-nums">{{ $donation['amountMin'] !== null ? 'Fr. '.number_format($donation['amountMin'], 2, '.', "'") : '–' }} / {{ $donation['amountMax'] !== null ? 'Fr. '.number_format($donation['amountMax'], 2, '.', "'") : '–' }}</dd></div>
                </dl>

                @if ($donation['comment'])
                    <div>
                        <flux:heading size="sm" level="3">Dein Kommentar</flux:heading>
                        <flux:text class="mt-1">{{ $donation['comment'] }}</flux:text>
                    </div>
                @endif
            </flux:card>
        @empty
            <flux:card class="rounded-xl border-hfm-light/40 bg-white text-center shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-hfm-light/25 text-hfm-dark dark:bg-slate-800 dark:text-hfm-light"><flux:icon.heart class="size-6" /></div>
                <flux:heading size="lg" level="2" class="mt-4">Noch keine Spende</flux:heading>
                <flux:text class="mt-2">Für den gewählten Anlass ist keine Spende vorhanden.</flux:text>
                @if ($donorRegistrationOpen)
                    <flux:button href="{{ route('become-donor') }}" wire:navigate icon="heart" variant="primary" class="mt-5">Beim aktuellen Anlass spenden</flux:button>
                @endif
            </flux:card>
        @endforelse
    </div>
@endsection
