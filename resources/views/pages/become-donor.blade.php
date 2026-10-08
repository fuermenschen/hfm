@extends('layouts.public')

@section('title', 'Spender:in werden')
@section('meta_description', 'Unterstütze Sportler:innen beim Spendenlauf Höhenmeter für Menschen mit einem Beitrag pro Runde.')

@section('content')
    <div>
        @component('components.page-title')
            Spender:in werden
        @endcomponent

        <div class="mx-auto w-full max-w-2xl text-left sm:text-center">
            Du lässt lieber andere schwitzen und möchtest als Spender:in einen Beitrag für Winterthurer
            Benefizpartner:innen leisten? Hier findest du alle Infos zum Spenden beim Anlass
            <strong>{{ $currentDonationEvent->title }}</strong>
            am {{ $currentDonationEvent->starts_at->translatedFormat('j. F Y') }}.
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
            @elseif ($currentDonationEvent?->donorRegistrationIsOpen())
                <div class="border-hfm-red/40 bg-hfm-red/10 mt-6 mb-9 rounded-lg border px-9 py-6">
                    <p class="text-hfm-red font-semibold">
                        Aktuell sind noch keine Sportler:innen angemeldet, für welche du dich als Spender:in eintragen
                        kannst.
                    </p>
                    <p class="mt-1">Versuche es später erneut oder melde dich für den Newsletter an.</p>
                </div>
            @else
                <x-registration-unavailable-notice
                    :event="$currentDonationEvent"
                    :closes-at="$currentDonationEvent->donor_registration_closes_at"
                    role="Spender:in"
                />
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
