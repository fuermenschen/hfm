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

        <section class="space-y-4">
            <div>
                <flux:heading size="lg" level="2">Deine Übersicht</flux:heading>
                <flux:text>Bestätigte Einträge werden in den Summen berücksichtigt.</flux:text>
            </div>

            @if ($hasAthleteRegistrations || $hasOwnDonations)
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @if ($hasAthleteRegistrations)
                        <a href="{{ route('portal.participations', $eventParameters) }}" wire:navigate class="group block rounded-2xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600">
                            <flux:card class="h-full space-y-4 rounded-2xl border-sky-200 bg-gradient-to-br from-sky-100/80 to-white shadow-sm transition group-hover:-translate-y-0.5 group-hover:shadow-md motion-reduce:transform-none motion-reduce:transition-none dark:border-sky-900/70 dark:from-sky-950/50 dark:to-slate-900">
                                <span class="flex size-10 items-center justify-center rounded-full bg-sky-200/80 text-sky-800 dark:bg-sky-900 dark:text-sky-200"><flux:icon.heart class="size-5" /></span>
                                <div>
                                    <flux:text>Eingegangene Spenden</flux:text>
                                    <flux:heading size="xl" class="mt-1 tabular-nums">{{ number_format($receivedDonationCount, 0, '.', "'") }}</flux:heading>
                                </div>
                                <flux:text>{{ $pendingReceivedDonationCount > 0 ? $pendingReceivedDonationCount.' noch nicht bestätigt' : 'Alle bestätigten Spenden' }}</flux:text>
                            </flux:card>
                        </a>

                        <a href="{{ route('portal.participations', $eventParameters) }}" wire:navigate class="group block rounded-2xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600">
                            <flux:card class="h-full space-y-4 rounded-2xl border-sky-200 bg-gradient-to-br from-sky-50 to-white shadow-sm transition group-hover:-translate-y-0.5 group-hover:shadow-md motion-reduce:transform-none motion-reduce:transition-none dark:border-sky-900/70 dark:from-sky-950/30 dark:to-slate-900">
                                <span class="flex size-10 items-center justify-center rounded-full bg-sky-100 text-sky-700 dark:bg-sky-900 dark:text-sky-200"><flux:icon.chart-bar class="size-5" /></span>
                                <div>
                                    <flux:text>Erwarteter Spendenbetrag</flux:text>
                                    <flux:heading size="xl" class="mt-1 tabular-nums"><span class="text-base font-medium">Fr.</span> {{ number_format($estimatedReceivedAmount, 2, '.', "'") }}</flux:heading>
                                </div>
                                <flux:text>Mit geschätzten Runden</flux:text>
                            </flux:card>
                        </a>

                        @if ($hasCompletedRounds)
                            <a href="{{ route('portal.participations', $eventParameters) }}" wire:navigate class="group block rounded-2xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600">
                                <flux:card class="h-full space-y-4 rounded-2xl border-emerald-200 bg-gradient-to-br from-emerald-50 to-white shadow-sm transition group-hover:-translate-y-0.5 group-hover:shadow-md motion-reduce:transform-none motion-reduce:transition-none dark:border-emerald-900/70 dark:from-emerald-950/30 dark:to-slate-900">
                                    <span class="flex size-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200"><flux:icon.check-circle class="size-5" /></span>
                                    <div>
                                        <flux:text>Effektiver Spendenbetrag</flux:text>
                                        <flux:heading size="xl" class="mt-1 tabular-nums"><span class="text-base font-medium">Fr.</span> {{ number_format($currentReceivedAmount, 2, '.', "'") }}</flux:heading>
                                    </div>
                                    <flux:text>Mit absolvierten Runden</flux:text>
                                </flux:card>
                            </a>
                        @endif
                    @endif

                    @if ($hasOwnDonations)
                        <a href="{{ route('portal.donations', $eventParameters) }}" wire:navigate class="group block rounded-2xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600">
                            <flux:card class="h-full space-y-4 rounded-2xl border-amber-200 bg-gradient-to-br from-amber-50 to-white shadow-sm transition group-hover:-translate-y-0.5 group-hover:shadow-md motion-reduce:transform-none motion-reduce:transition-none dark:border-amber-900/70 dark:from-amber-950/30 dark:to-slate-900">
                                <span class="flex size-10 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200"><flux:icon.hand-raised class="size-5" /></span>
                                <div>
                                    <flux:text>Eigene Spenden</flux:text>
                                    <flux:heading size="xl" class="mt-1 tabular-nums">{{ number_format($ownDonationCount, 0, '.', "'") }}</flux:heading>
                                </div>
                                <flux:text>{{ $pendingOwnDonationCount > 0 ? $pendingOwnDonationCount.' noch nicht bestätigt' : 'Alle bestätigten Spenden' }}</flux:text>
                            </flux:card>
                        </a>
                    @endif
                </div>
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
