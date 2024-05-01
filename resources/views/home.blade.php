@props(['athleteCount', 'donationCount'])

@extends('layouts.public')

@section('content')
    <div>
        @component('components.page-title')
            Höhenmeter für Menschen
        @endcomponent
        <div class="space-y-sm">
            <p>
                Mach mit beim ersten "Höhenmeter für Menschen" und werde Teil einer Gemeinschaft, die etwas bewirken
                will!
                Deine Teilnahme hilft dabei, wichtige Spenden für lokale Wohltätigkeitsorganisationen zu sammeln, die
                sich
                für die Bedürftigen in unserer Gemeinde einsetzen. Egal ob du läufst, gehst oder rollst, jeden Meter,
                den du
                erklimmst, hilft unseren Benefizpartner:innen. Schon <span class="text-hfm-red">{{ $athleteCount }} Sportler:innen </span>
                haben
                sich
                registriert. Bist du der:die Nächste?
            </p>
            <p>
                Oder möchtest du die Benefizpartner:innen lieber mit einer Spende unterstützen? Dann werde
                Spender:in und bringe mit deiner Spende etwas in bewegung - im wahrsten Sinne des Wortes. Schon
                <span class="text-hfm-red">{{ $donationCount }} Spenden-Anmeldungen </span> sind eingegangen. Hilf uns,
                noch mehr
                zu erreichen!
            </p>
        </div>

        <x-page-subtitle>Unsere Benefizpartner:innen</x-page-subtitle>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-md justify-items-center">
            <img src="https://picsum.photos/100/100" alt="Brühlgut Stiftung">
            <img src="https://picsum.photos/100/100" alt="Brühlgut STiftung">
            <img src="https://picsum.photos/100/100" alt="Brühlgut STiftung">
        </div>

        <div class="flex flex-col space-y-sm sm:grid sm:grid-cols-2 max-w-full sm:space-y-0 sm:gap-md mt-lg">
            <x-button icon="information-circle" label="Erfahre mehr" href="/ueber-das-projekt" wire:navigate red xl />
            <x-button icon="fire" label="Jetzt Sportler:in werden" href="/sportlerin-werden" wire:navigate
                      red xl />
            <x-button icon="heart" label="Jetzt Spender:in werden" href="/spenderin-werden" wire:navigate
                      red xl />
            <x-button icon="flag" label="Details zum Anlass" href="/informationen-zum-anlass" wire:navigate red xl />
        </div>
    </div>

@endsection
