@props(['first_name', 'last_name', 'email'])

@extends('layouts.public')

@section('content')
    @component('components.page-title')
        Mitgliederversammlung
    @endcomponent

    <div class="w-full max-w-2xl mx-auto text-left sm:text-center flex flex-col space-y-sm">
        <span>Auf dieser Seite kannst du dich für die Mitgliederversammlung anmelden und findest alle relevanten Informationen.</span>
    </div>


    <div class="flex flex-col md:grid md:grid-cols-2 gap-4 mt-12">
        <flux:card class="w-full bg-white/50 shadow-md">
            <flux:heading size="xl" class="mb-6">Eckdaten</flux:heading>
            <dl class="space-y-5">
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
                    <div class="flex gap-4">
                        <flux:icon.calendar class="w-6 h-6" />
                        <dt class="w-24 font-semibold">Datum</dt>
                    </div>
                    <dd class="flex-1">16. Mai 2025</dd>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
                    <div class="flex gap-4">
                        <flux:icon.clock class="w-6 h-6" />
                        <dt class="w-24 font-semibold">Uhrzeit</dt>
                    </div>
                    <dd class="flex-1">19 Uhr</dd>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
                    <div class="flex gap-4">
                        <flux:icon.map-pin class="w-6 h-6" />
                        <dt class="w-24 font-semibold">Ort</dt>
                    </div>
                    <dd class="flex-1">
                        EventSalzHalle<br>
                        <span class="text-sm leading-snug block">
                        (Eventlocation Salzhaus, ehem. Terra Brocki,<br>
                        Untere Vogelsangstrasse 4, 8400 Winterthur)
                    </span>
                    </dd>
                </div>
                <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
                    <div class="flex gap-4">
                        <flux:icon.sparkles class="w-6 h-6" />
                        <dt class="w-24 font-semibold mt-0">Verpflegung</dt>
                    </div>
                    <dd class="flex-1">
                        Es gibt etwas zu Trinken und kleine Snacks, nichts grosses.
                    </dd>
                </div>
            </dl>
        </flux:card>

        <flux:card class="w-full bg-white/50 shadow-md">
            <flux:heading size="xl" class="mb-6">Anmeldung</flux:heading>
            @livewire('association-general-meeting-registration-form', [
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
            ])
        </flux:card>

        <flux:card class="w-full bg-white/50 shadow-md">
            <flux:heading size="xl" class="mb-6">Dokumente</flux:heading>
            <div class="flex flex-col sm:flex-row gap-4 flex-wrap">
                <flux:button
                    href="{{ Vite::asset('resources/files/statuten.pdf') }}"
                    target="_blank"
                    variant="filled"
                    icon-trailing="document-arrow-down"
                >Statuten
                </flux:button>
                <flux:button
                    href="{{ Vite::asset('resources/files/mv2025_protokoll_gruendungsversammlung.pdf') }}"
                    target="_blank"
                    variant="filled"
                    icon-trailing="document-arrow-down"
                >Protokoll der Gründungsversammlung
                </flux:button>
                <flux:button
                    href="{{ Vite::asset('resources/files/mv2025_budget_verein.pdf') }}"
                    target="_blank"
                    variant="filled"
                    icon-trailing="document-arrow-down"
                >Budget 2025
                </flux:button>
                <flux:button
                    href="{{ Vite::asset('resources/files/mv2025_budget_hfm.pdf') }}"
                    target="_blank"
                    variant="filled"
                    icon-trailing="document-arrow-down"
                >Budget HfM 2025
                </flux:button>
            </div>
        </flux:card>
    </div>

@endsection
