@extends('layouts.portal')

@section('title', 'Spenden')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:heading size="xl" level="1">Spenden</flux:heading>
                <flux:text class="mt-2 text-base">Deine Spenden an Sportler:innen.</flux:text>
            </div>

            <x-portal.event-filter :events="$events" :selected-event-slug="$selectedEventSlug" />
        </div>

        <x-portal.success-message />

        @forelse ($donations as $donation)
            <flux:card class="space-y-6">
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
                    <flux:callout icon="exclamation-triangle" variant="warning">
                        <flux:callout.heading>Spende noch nicht bestätigt</flux:callout.heading>
                        <x-slot name="actions">
                            <livewire:portal-confirmation-button type="donation" :record-id="$donation['id']" :wire:key="'registration-donation-'.$donation['id']" />
                        </x-slot>
                    </flux:callout>
                @endunless

                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Sportart</dt><dd class="mt-1 font-medium">{{ $donation['sport'] }}</dd></div>
                    <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Begünstigte</dt><dd class="mt-1 font-medium">{{ $donation['partner'] }}</dd></div>
                    <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Pro Runde</dt><dd class="mt-1 font-medium">Fr. {{ number_format($donation['amountPerRound'], 2, '.', "'") }}</dd></div>
                    <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Minimum / Maximum</dt><dd class="mt-1 font-medium">{{ $donation['amountMin'] !== null ? 'Fr. '.number_format($donation['amountMin'], 2, '.', "'") : '–' }} / {{ $donation['amountMax'] !== null ? 'Fr. '.number_format($donation['amountMax'], 2, '.', "'") : '–' }}</dd></div>
                    <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Erwarteter Betrag</dt><dd class="mt-1 font-medium">Fr. {{ number_format($donation['estimatedAmount'], 2, '.', "'") }}</dd></div>
                    <div><dt class="text-sm text-zinc-500 dark:text-zinc-400">Aktueller Betrag</dt><dd class="mt-1 font-medium">Fr. {{ number_format($donation['currentAmount'], 2, '.', "'") }}</dd></div>
                </dl>

                @if ($donation['comment'])
                    <div>
                        <flux:heading size="sm" level="3">Dein Kommentar</flux:heading>
                        <flux:text class="mt-1">{{ $donation['comment'] }}</flux:text>
                    </div>
                @endif
            </flux:card>
        @empty
            <flux:callout icon="information-circle">
                <flux:callout.heading>Keine Spenden gefunden</flux:callout.heading>
                <flux:callout.text>Für den gewählten Anlass sind keine Spenden vorhanden.</flux:callout.text>
            </flux:callout>
        @endforelse
    </div>
@endsection
