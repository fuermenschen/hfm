@extends('layouts.public')

@section('content')
    <div>
        @component('components.page-title')
            Sportler:in werden
        @endcomponent

        <div
            class="w-full max-w-2xl mx-auto text-left sm:text-center">Du möchtest als Sportler:in dein Bestes geben und
            damit Winterthurer Benefizpartner:innen unterstützen? Hier bist du goldrichtig zur Anmeldung.
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

        <div class="mt-sm mb-md rounded-lg border border-hfm-red/40 bg-hfm-red/10 px-md py-sm">
            <p class="font-semibold text-hfm-red">Die Anmeldung als Sportler:in ist aktuell noch nicht offen.</p>
            <p class="mt-1">Melde dich für den Newsletter an. Wir informieren dich sofort, sobald die Anmeldung startet.</p>
        </div>

        {{--
        Es freut uns, dass du als Sportler:in bei uns mitmachen möchtest. Bitte fülle das Formular aus, damit wir alle
        nötigen Informationen zu dir haben.
        @livewire('become-athlete-form')
        --}}

        <x-page-subtitle>
            Newsletter Anmeldung
        </x-page-subtitle>
        @livewire('newsletter-registration-form')
    </div>
@endsection
