@extends('layouts.public')

@section('content')
    <div>
        @component('components.page-title')
            Spender:in werden
        @endcomponent

        <div class="mx-auto w-full max-w-2xl text-left sm:text-center">
            Du lässt lieber andere schwitzen und möchtest als Spender:in einen Beitrag für Winterthurer
            Benefizpartner:innen leisten? Hier bist du goldrichtig zur Anmeldung.
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
            @if ($currentDonationEvent?->donorRegistrationIsOpen() && $hasVerifiedAthletes)
                @livewire('donor-registration-wizard')
            @else
                <div class="border-hfm-red/40 bg-hfm-red/10 mt-6 mb-9 rounded-lg border px-9 py-6">
                    @if ($currentDonationEvent?->donorRegistrationIsOpen())
                        <p class="text-hfm-red font-semibold">Aktuell sind noch keine Sportler:innen angemeldet.</p>
                        <p class="mt-1">Versuche es später erneut oder melde dich für den Newsletter an.</p>
                    @else
                        <p class="text-hfm-red font-semibold">
                            Die Anmeldung als Spender:in ist aktuell noch nicht offen.
                        </p>
                        <p class="mt-1">
                            Melde dich für den Newsletter an. Wir informieren dich, sobald das Spendenformular wieder
                            verfügbar ist.
                        </p>
                    @endif
                </div>
            @endif
        @endauth

        @guest('web')
            @unless ($currentDonationEvent?->donorRegistrationIsOpen() && $hasVerifiedAthletes)
                <x-page-subtitle> Newsletter Anmeldung </x-page-subtitle>
                @livewire('newsletter-registration-form')
            @endunless
        @endguest
    </div>
@endsection
