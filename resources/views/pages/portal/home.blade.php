@extends('layouts.portal')

@section('title', 'Portal')

@section('content')
    <div class="space-y-8">
        <x-portal.page-header
            :title="$greeting.$firstName"
            subtitle="Danke für dis Engagement."
            :events="$events"
            :selected-event-slug="$selectedEventSlug"
        />

        <x-portal.success-message />

        @if ($pendingParticipations !== [] || $pendingDonations !== [])
            <section class="space-y-4" aria-labelledby="pending-confirmations">
                <div>
                    <flux:heading id="pending-confirmations" size="lg" level="2">Noch 1 Schritt</flux:heading>
                    <flux:text>Offene Bestätigungen aus allen Anlässen.</flux:text>
                </div>

                @foreach ($pendingParticipations as $participation)
                    <flux:card class="rounded-2xl border-amber-200 bg-gradient-to-br from-amber-50 to-white shadow-sm dark:border-amber-900/70 dark:from-amber-950/40 dark:to-slate-900">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/70 dark:text-amber-200">
                                    <flux:icon.trophy class="size-5" />
                                </span>
                                <div>
                                    <flux:heading level="3">Teilnahme bestätigen</flux:heading>
                                    <flux:text class="mt-1">
                                        {{ $participation['event'] }}{{ $participation['eventDate'] ? ' · '.$participation['eventDate'] : '' }}<br>
                                        {{ $participation['sport'] }} · {{ $participation['roundsEstimated'] }} geschätzte Runden · {{ $participation['partner'] }}
                                    </flux:text>
                                </div>
                            </div>
                            <livewire:portal-confirmation-button type="athlete" :record-id="$participation['id']" :wire:key="'pending-athlete-'.$participation['id']" />
                        </div>
                    </flux:card>
                @endforeach

                @foreach ($pendingDonations as $donation)
                    <flux:card class="rounded-2xl border-amber-200 bg-gradient-to-br from-amber-50 to-white shadow-sm dark:border-amber-900/70 dark:from-amber-950/40 dark:to-slate-900">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/70 dark:text-amber-200">
                                    <flux:icon.heart class="size-5" />
                                </span>
                                <div>
                                    <flux:heading level="3">Spende bestätigen</flux:heading>
                                    <flux:text class="mt-1">
                                        {{ $donation['event'] }}{{ $donation['eventDate'] ? ' · '.$donation['eventDate'] : '' }} · {{ $donation['athlete'] }}<br>
                                        Erwartet: Fr. {{ number_format($donation['estimatedAmount'], 2, '.', "'") }} · {{ $donation['amountMax'] !== null ? 'Maximal Fr. '.number_format($donation['amountMax'], 2, '.', "'") : 'Ohne Maximalbetrag' }}
                                    </flux:text>
                                </div>
                            </div>
                            <livewire:portal-confirmation-button type="donation" :record-id="$donation['id']" :wire:key="'pending-donation-'.$donation['id']" />
                        </div>
                    </flux:card>
                @endforeach
            </section>
        @endif

        <section class="space-y-6">
            <div>
                <flux:heading size="lg" level="2">Dein Engagement</flux:heading>
                <flux:text>Geldsummen berücksichtigen nur bestätigte Einträge.</flux:text>
            </div>

            @if ($hasAthleteRegistrations || $hasOwnDonations)
                @if ($hasAthleteRegistrations)
                    <section class="space-y-3" aria-labelledby="participation-summary">
                        <div>
                            <flux:heading id="participation-summary" level="3">Deine Teilnahme</flux:heading>
                            <flux:text>Was du mit deinen Runden für deine Begünstigte sammelst.</flux:text>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            <x-portal.summary-card :href="route('portal.participations', $eventParameters)" icon="user-group" label="Bestätigte Unterstützer:innen" :value="number_format($receivedDonationCount, 0, '.', chr(39))" :detail="$pendingReceivedDonationCount > 0 ? $pendingReceivedDonationCount.' noch nicht bestätigt' : 'Keine offenen Bestätigungen'" />
                            <x-portal.summary-card :href="route('portal.participations', $eventParameters)" icon="calculator" label="Voraussichtlich gesammelt" :value="'Fr. '.number_format($estimatedReceivedAmount, 2, '.', chr(39))" detail="Mit deinen geschätzten Runden" />
                            @if ($hasCompletedRounds)
                                <x-portal.summary-card :href="route('portal.participations', $eventParameters)" icon="chart-bar" label="Aktuell gesammelt" :value="'Fr. '.number_format($currentReceivedAmount, 2, '.', chr(39))" detail="Mit deinen absolvierten Runden" />
                            @endif
                        </div>
                    </section>
                @endif

                @if ($hasOwnDonations)
                    <section class="space-y-3" aria-labelledby="donation-summary">
                        <div>
                            <flux:heading id="donation-summary" level="3">Deine Unterstützung</flux:heading>
                            <flux:text>Was du mit deinen Spenden an Sportler:innen beiträgst.</flux:text>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            <x-portal.summary-card :href="route('portal.donations', $eventParameters)" icon="heart" label="Bestätigte Spenden" :value="number_format($ownDonationCount, 0, '.', chr(39))" :detail="$pendingOwnDonationCount > 0 ? $pendingOwnDonationCount.' noch nicht bestätigt' : 'Keine offenen Bestätigungen'" />
                            <x-portal.summary-card :href="route('portal.donations', $eventParameters)" icon="calculator" label="Voraussichtlicher eigener Beitrag" :value="'Fr. '.number_format($estimatedOwnAmount, 2, '.', chr(39))" detail="Mit geschätzten Runden aller Sportler:innen" />
                            @if ($hasOwnCompletedRounds)
                                <x-portal.summary-card :href="route('portal.donations', $eventParameters)" icon="chart-bar" label="Aktueller eigener Beitrag" :value="'Fr. '.number_format($currentOwnAmount, 2, '.', chr(39))" detail="Mit absolvierten Runden der Sportler:innen" />
                            @endif
                        </div>
                    </section>
                @endif
            @else
                <flux:card class="rounded-2xl border-sky-200 bg-gradient-to-br from-sky-50 to-white text-center shadow-sm dark:border-sky-900/70 dark:from-sky-950/30 dark:to-slate-900">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-200">
                        <flux:icon.sparkles class="size-6" />
                    </div>
                    <flux:heading size="lg" level="3" class="mt-4">Hier beginnt dein Engagement</flux:heading>
                    <flux:text class="mx-auto mt-2 max-w-xl">Melde dich beim aktuellen Anlass an oder unterstütze eine:n Sportler:in.</flux:text>
                    <div class="mt-5 flex flex-col justify-center gap-3 sm:flex-row">
                        @if ($athleteRegistrationOpen)
                            <flux:button href="{{ route('become-athlete') }}" wire:navigate icon="trophy">Als Sportler:in anmelden</flux:button>
                        @endif
                        @if ($donorRegistrationOpen)
                            <flux:button href="{{ route('become-donor') }}" wire:navigate icon="heart" variant="primary">Spende anmelden</flux:button>
                        @endif
                    </div>
                </flux:card>
            @endif
        </section>
    </div>
@endsection
