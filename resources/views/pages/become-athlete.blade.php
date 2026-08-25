@extends('layouts.public')

@section('content')
    <div>
        @component('components.page-title')
            Sportler:in werden
        @endcomponent

        <div class="mx-auto w-full max-w-2xl text-left sm:text-center">
            Du möchtest als Sportler:in dein Bestes geben und damit Winterthurer Benefizpartner:innen unterstützen? Hier
            bist du goldrichtig zur Anmeldung.
        </div>

        <div class="mx-auto mt-12 w-full max-w-2xl text-left sm:text-center">
            Bist du noch nicht ganz sicher, wie das alles funktioniert oder hast du Fragen? Schau bei den
            <x-inline-link href=" {{ route('questions-and-answers') }}">Fragen und Antworten</x-inline-link>
            vorbei.
        </div>

        <x-page-subtitle> Anmeldeformular </x-page-subtitle>

        @auth('web')
            <div class="border-hfm-red/40 bg-hfm-red/10 mt-6 mb-9 rounded-lg border px-9 py-6">
                <p class="text-hfm-red font-semibold">Du bist als Admin angemeldet.</p>
                <p class="mt-1">
                    Bitte logge dich aus oder öffne einen privaten Browser-Tab, um das Formular zu sehen.
                </p>
            </div>
        @else
            @if ($currentAthleteRegistration ?? null)
                <div class="border-hfm-red/40 bg-hfm-red/10 mt-6 mb-9 rounded-lg border px-9 py-6">
                    <p class="text-hfm-red font-semibold">
                        Du bist für diesen Anlass bereits als Sportler:in angemeldet.
                    </p>
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
                <flux:callout icon="user-group" variant="secondary" class="mb-6">
                    <flux:callout.heading>Als Gruppe teilnehmen?</flux:callout.heading>
                    <flux:callout.text>
                        Jede Person meldet sich zunächst einzeln als Sportler:in an. Nach Bestätigung der Teilnahme
                        können im Portal Gruppen gegründet oder Beitrittsanfragen gestellt werden.</flux:callout.text>
                </flux:callout>
                @livewire('athlete-registration-wizard')
            @else
                <div class="border-hfm-red/40 bg-hfm-red/10 mt-6 mb-9 rounded-lg border px-9 py-6">
                    <p class="text-hfm-red font-semibold">
                        Die Anmeldung als Sportler:in ist aktuell noch nicht offen.
                    </p>
                    <p class="mt-1">
                        Melde dich für den Newsletter an. Wir informieren dich sofort, sobald die Anmeldung startet.
                    </p>
                </div>
            @endif
        @endauth

        @guest('web')
            @unless ($currentDonationEvent?->athleteRegistrationIsOpen())
                <x-page-subtitle> Newsletter Anmeldung </x-page-subtitle>
                @livewire('newsletter-registration-form')
            @endunless
        @endguest
    </div>
@endsection
