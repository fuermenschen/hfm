@extends('layouts.public')

@section('content')
    <div>
        @component('components.page-title')
            Spender:in werden
        @endcomponent
        <div
            class="w-full max-w-2xl mx-auto text-left sm:text-center">Du lässt lieber andere schwitzen und möchtest als
            Spender:in einen Beitrag für Winterthurer Benefizpartner:innen leisten? Hier bist du goldrichtig zur
            Anmeldung.
        </div>

        <div
            class="w-full max-w-2xl mx-auto text-left sm:text-center mt-lg">Bist du noch nicht ganz sicher, wie das
            alles funktioniert oder hast du Fragen? Schau bei den
            <x-inline-link href=" {{ route('questions-and-answers') }}">Fragen und Antworten</x-inline-link>
            vorbei.
        </div>

        <x-page-subtitle>
            Anmeldeformular
        </x-page-subtitle>
        Es freut uns, dass du Spender:in werden möchtest. Indem du das Formular ausfüllst, hilfst du den Sportler:innen,
        Spenden für unsere Benefizpartner:innen zu sammeln.

        @livewire('become-donator-form')
    </div>
@endsection
