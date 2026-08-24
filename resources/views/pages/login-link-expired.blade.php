@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-2xl text-left sm:text-center">
        @component('components.page-title')
            Login-Link
        @endcomponent

        <x-page-subtitle> Link ist abgelaufen oder ungültig </x-page-subtitle>

        <p class="mb-6">
            Dieser Login-Link ist nicht mehr gültig. Login-Links sind aus Sicherheitsgründen nur kurze Zeit verwendbar.
        </p>

        <p class="mb-6">Fordere einen neuen Login-Link an, um dich anzumelden.</p>

        <div class="flex flex-col justify-center gap-4 sm:flex-row">
            <flux:button
                href="{{ route('login', ['redirect' => $intendedDestination]) }}"
                icon="envelope"
                wire:navigate
            >
                Neuen Login-Link anfordern
            </flux:button>

            <flux:button variant="ghost" href="{{ route('home') }}" wire:navigate> Zurück zur Startseite </flux:button>
        </div>

        @if (isset($intendedDestination) && $intendedDestination !== null)
            <p class="mt-6 text-sm text-gray-600 dark:text-gray-400">
                @switch ($intendedDestination)
                    @case ('become-athlete')
                        Nach dem Login gelangst du zur Sportler:innen-Anmeldung.
                        @break
                    @case ('become-donor')
                        Nach dem Login gelangst du zur Spender:innen-Anmeldung.
                        @break
                @endswitch
            </p>
        @endif
    </div>
@endsection
