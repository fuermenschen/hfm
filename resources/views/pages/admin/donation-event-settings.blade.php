@php($donationEvent = $donationEvent ?? null)

@component('layouts.admin', ['title' => $donationEvent === null ? 'Anlass erstellen' : 'Anlass bearbeiten'])
    @section('content')
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    @if ($donationEvent !== null)
                        <flux:heading size="lg">{{ $donationEvent->title }} ({{ $donationEvent->slug }})</flux:heading>
                        <flux:subheading>Anlassdaten und öffentliche Inhalte verwalten.</flux:subheading>
                    @else
                        <flux:subheading>Nach dem Erstellen können Partner:innen und Sponsor:innen zugeordnet werden.</flux:subheading>
                    @endif
                </div>

                <flux:button as="a" href="{{ route('admin.donation-events.index') }}" variant="ghost" icon="arrow-left" wire:navigate.hover>
                    Zur Übersicht
                </flux:button>
            </div>

            @livewire('admin-donation-event-form', ['donationEvent' => $donationEvent])
        </div>
    @endsection
@endcomponent
