@extends('layouts.portal')

@section('title', 'Portal')

@section('content')
    <div class="space-y-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ $greeting }}{{ $firstName }}</flux:heading>
                <flux:text class="mt-2 text-base">Hier findest du den aktuellen Stand deiner Teilnahmen und Spenden.</flux:text>
            </div>

            <x-portal.event-filter :events="$events" :selected-event-slug="$selectedEventSlug" />
        </div>

        <x-portal.success-message />

        @if ($pendingParticipations !== [] || $pendingDonations !== [])
            <div class="space-y-4">
                @foreach ($pendingParticipations as $participation)
                    <flux:callout icon="exclamation-triangle" variant="warning">
                        <flux:callout.heading>Teilnahme bestätigen</flux:callout.heading>
                        <flux:callout.text>
                            {{ $participation['event'] }} · {{ $participation['sport'] }} · {{ $participation['roundsEstimated'] }} geschätzte Runden · {{ $participation['partner'] }}
                        </flux:callout.text>
                        <x-slot name="actions">
                            <livewire:portal-confirmation-button type="athlete" :record-id="$participation['id']" :wire:key="'pending-athlete-'.$participation['id']" />
                        </x-slot>
                    </flux:callout>
                @endforeach

                @foreach ($pendingDonations as $donation)
                    <flux:callout icon="exclamation-triangle" variant="warning">
                        <flux:callout.heading>Spende bestätigen</flux:callout.heading>
                        <flux:callout.text>
                            {{ $donation['event'] }} · {{ $donation['athlete'] }} · Erwarteter Betrag Fr. {{ number_format($donation['estimatedAmount'], 2, '.', "'") }} · {{ $donation['amountMax'] !== null ? 'Maximalbetrag Fr. '.number_format($donation['amountMax'], 2, '.', "'") : 'Kein Maximalbetrag' }}
                        </flux:callout.text>
                        <x-slot name="actions">
                            <livewire:portal-confirmation-button type="donation" :record-id="$donation['id']" :wire:key="'pending-donation-'.$donation['id']" />
                        </x-slot>
                    </flux:callout>
                @endforeach
            </div>
        @endif

        <section class="space-y-4">
            <div>
                <flux:heading size="lg" level="2">{{ $selectedEvent?->title ?? 'Alle Anlässe' }}</flux:heading>
                <flux:text>Bestätigte Einträge werden in den Summen berücksichtigt.</flux:text>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 {{ $hasCompletedRounds ? 'xl:grid-cols-4' : 'xl:grid-cols-3' }}">
                <flux:card class="space-y-2">
                    <flux:text>Eingegangene Spenden</flux:text>
                    <flux:heading size="xl">{{ number_format($receivedDonationCount, 0, '.', "'") }}</flux:heading>
                    @if ($pendingReceivedDonationCount > 0)
                        <flux:text>{{ $pendingReceivedDonationCount }} noch nicht bestätigt</flux:text>
                    @endif
                </flux:card>

                <flux:card class="space-y-2">
                    <flux:text>Erwarteter Spendenbetrag</flux:text>
                    <flux:heading size="xl">Fr. {{ number_format($estimatedReceivedAmount, 2, '.', "'") }}</flux:heading>
                    <flux:text>Mit geschätzten Runden</flux:text>
                </flux:card>

                @if ($hasCompletedRounds)
                    <flux:card class="space-y-2">
                        <flux:text>Effektiver Spendenbetrag</flux:text>
                        <flux:heading size="xl">Fr. {{ number_format($currentReceivedAmount, 2, '.', "'") }}</flux:heading>
                        <flux:text>Mit absolvierten Runden</flux:text>
                    </flux:card>
                @endif

                <flux:card class="space-y-2">
                    <flux:text>Eigene Spenden</flux:text>
                    <flux:heading size="xl">{{ number_format($ownDonationCount, 0, '.', "'") }}</flux:heading>
                    @if ($pendingOwnDonationCount > 0)
                        <flux:text>{{ $pendingOwnDonationCount }} noch nicht bestätigt</flux:text>
                    @endif
                </flux:card>
            </div>
        </section>
    </div>
@endsection
