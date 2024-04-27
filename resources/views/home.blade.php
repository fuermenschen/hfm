@extends('layouts.public')

@section('content')
    <div>
        @component('components.page-title')
            Höhenmeter für Menschen
        @endcomponent
        Es haben sich schon {{ $athleteCount }} Sportler:innen registriert. Mache auch du mit bei der wohltätigen
        Aktion!

        <div class="flex flex-col space-y-sm sm:grid sm:grid-cols-2 max-w-full sm:space-y-0 sm:gap-md mt-md">
            <x-button icon="information-circle" label="Erfahre mehr" href="/ueber-das-projekt" wire:navigate red xl />
            <x-button icon="fire" label="Jetzt Sportler:in werden" href="/sportlerin-werden" wire:navigate
                      red xl />
            <x-button icon="heart" label="Jetzt Spender:in werden" href="/spenderin-werden" wire:navigate
                      red xl />
            <x-button icon="flag" label="Details zum Anlass" href="/informationen-zum-anlass" wire:navigate red xl />
        </div>
    </div>

@endsection
