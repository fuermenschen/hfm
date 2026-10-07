@extends('layouts.base')

@section('body')
    <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col justify-between">
        <div>
            <x-public-menu />

            <div
                class="relative m-auto w-full p-9 pt-12 sm:items-center sm:justify-center"
                style="--content-pt: 48px; --content-pb: 36px"
            >
                @if (isset($publicDonationEvent) && $publicDonationEvent->id !== $currentDonationEvent?->id && ($currentDonationEvent?->athleteRegistrationIsOpen() || $currentDonationEvent?->donorRegistrationIsOpen()))
                    <p class="mb-6 text-sm text-zinc-600 dark:text-zinc-400">
                        Anmeldungen über das Menü beziehen sich auf {{ $currentDonationEvent->title }} am {{ $currentDonationEvent->starts_at->translatedFormat('j. F Y') }}.
                    </p>
                @endif
                @yield('content')
            </div>
        </div>

        <x-public-footer />
    </div>

    @isset($slot)
        {{ $slot }}
    @endisset
@endsection
