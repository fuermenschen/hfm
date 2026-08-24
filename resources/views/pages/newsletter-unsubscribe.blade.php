@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-2xl text-left sm:text-center">
        @component('components.page-title')
            Newsletter
        @endcomponent

        <x-page-subtitle> Newsletter Abmeldung </x-page-subtitle>

        @if ($isUnsubscribed)
            <p class="mb-6">Deine E-Mail-Adresse {{ $email }} wurde erfolgreich vom Newsletter abgemeldet.</p>
        @else
            <p class="mb-6">Möchtest du die E-Mail-Adresse {{ $email }} wirklich vom Newsletter abmelden?</p>

            @if ($hasError)
                <p class="mb-6 text-red-700">
                    Die Abmeldung konnte nicht verarbeitet werden. Bitte versuche es erneut.
                </p>
            @endif

            <form method="POST" action="{{ request()->fullUrl() }}">
                @csrf

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-md bg-red-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-red-800"
                >
                    Jetzt abmelden
                </button>
            </form>
        @endif
    </div>
@endsection
