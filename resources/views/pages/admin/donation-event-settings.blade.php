@php($donationEvent = $donationEvent ?? null)

@component('layouts.admin', ['title' => $donationEvent === null ? 'Anlass erstellen' : 'Anlass bearbeiten'])
    @section('content')
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="space-y-2">
                    @if ($donationEvent !== null)
                        <flux:heading size="lg">{{ $donationEvent->title }} ({{ $donationEvent->slug }})</flux:heading>
                        <flux:subheading>Anlassdaten und öffentliche Inhalte verwalten.</flux:subheading>
                        <div class="flex flex-wrap gap-2">
                            @if ($isCurrentEvent ?? false)
                                <flux:badge color="blue" icon="star">Aktueller Anlass</flux:badge>
                            @endif
                            <flux:badge
                                :color="$donationEvent->is_published ? 'green' : 'zinc'"
                                :icon="$donationEvent->is_published ? 'globe-alt' : 'pencil-square'"
                            >
                                {{ $donationEvent->is_published ? 'Veröffentlicht' : 'Entwurf' }}
                            </flux:badge>
                        </div>
                    @else
                        <flux:subheading
                            >Nach dem Erstellen können Partner:innen und Sponsor:innen zugeordnet
                            werden.</flux:subheading>
                    @endif
                </div>

                <flux:button
                    as="a"
                    href="{{ route('admin.donation-events.index') }}"
                    variant="ghost"
                    icon="arrow-left"
                    wire:navigate.hover
                >
                    Zur Übersicht
                </flux:button>
            </div>

            @if ($donationEvent === null)
                @livewire('admin-donation-event-form')
            @else
                <flux:tab.group>
                    <flux:tabs scrollable scrollable:fade>
                        <flux:tab name="event">Anlass</flux:tab>
                        <flux:tab name="partners">Partner:innen</flux:tab>
                        <flux:tab name="sponsors">Sponsor:innen</flux:tab>
                    </flux:tabs>

                    <flux:tab.panel name="event" class="pt-6">
                        @livewire('admin-donation-event-form', ['donationEvent' => $donationEvent, 'isCurrentEvent' => $isCurrentEvent ?? false])
                    </flux:tab.panel>

                    <flux:tab.panel name="partners" class="pt-6">
                        @livewire('admin-donation-event-partners-form', ['donationEvent' => $donationEvent])
                    </flux:tab.panel>

                    <flux:tab.panel name="sponsors" class="pt-6">
                        @livewire('admin-donation-event-sponsors-form', ['donationEvent' => $donationEvent])
                    </flux:tab.panel>
                </flux:tab.group>
            @endif
        </div>
    @endsection
@endcomponent
