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
            class="w-full max-w-2xl mx-auto text-left sm:text-center mt-12">Bist du noch nicht ganz sicher, wie das
            alles funktioniert oder hast du Fragen? Schau bei den
            <x-inline-link href=" {{ route('questions-and-answers') }}">Fragen und Antworten</x-inline-link>
            vorbei.
        </div>


        <x-page-subtitle>
            Anmeldeformular
        </x-page-subtitle>

        @auth('web')
            <div class="mt-6 mb-9 rounded-lg border border-hfm-red/40 bg-hfm-red/10 px-9 py-6">
                <p class="font-semibold text-hfm-red">Du bist als Admin angemeldet.</p>
                <p class="mt-1">Bitte logge dich aus oder öffne einen privaten Browser-Tab, um das Formular zu sehen.</p>
            </div>
        @else
            @if ($currentAthleteRegistration ?? null)
                <div class="mt-6 mb-9 rounded-lg border border-hfm-red/40 bg-hfm-red/10 px-9 py-6">
                    <p class="font-semibold text-hfm-red">Du bist für diesen Anlass bereits als Sportler:in angemeldet.</p>
                    <p class="mt-1">
                        @if ($currentAthleteRegistration->verified)
                            Du findest deine Anmeldung im Portal.
                        @else
                            Bitte bestätige deine Anmeldung über den Link in deiner E-Mail oder im Portal.
                        @endif
                    </p>
                    <p class="mt-4">
                        <x-inline-link href="{{ route('portal.dashboard') }}">Zum Portal</x-inline-link>
                    </p>
                </div>
            @elseif ($currentDonationEvent?->athleteRegistrationIsOpen())
                @livewire('athlete-registration-wizard')
            @else
                <div class="mt-6 mb-9 rounded-lg border border-hfm-red/40 bg-hfm-red/10 px-9 py-6">
                    <p class="font-semibold text-hfm-red">Die Anmeldung als Sportler:in ist aktuell noch nicht offen.</p>
                    <p class="mt-1">Melde dich für den Newsletter an. Wir informieren dich sofort, sobald die Anmeldung startet.</p>
                </div>
            @endif
        @endauth

        @guest('web')
            @unless ($currentDonationEvent?->athleteRegistrationIsOpen())
                <x-page-subtitle>
                    Newsletter Anmeldung
                </x-page-subtitle>
                @livewire('newsletter-registration-form')
            @endunless
        @endguest
    </div>
@endsection
