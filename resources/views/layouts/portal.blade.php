@extends('layouts.base')

@section('body')
    @php
        $eventParameters = $selectedEventSlug !== null
            ? ['anlass' => $selectedEventSlug]
            : (request()->query->has('anlass') ? ['anlass' => ''] : []);
    @endphp

    <flux:sidebar sticky collapsible="mobile" class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <flux:sidebar.brand href="{{ route('portal.dashboard', $eventParameters) }}" alt="Höhenmeter für Menschen" wire:navigate>
                <x-slot:logo class="rounded-none! overflow-visible!">
                    <img src="{{ Vite::asset('resources/images/logo_light.svg') }}" alt="" class="h-6 dark:hidden" />
                    <img src="{{ Vite::asset('resources/images/logo_dark.svg') }}" alt="" class="hidden h-6 dark:block" />
                </x-slot:logo>
            </flux:sidebar.brand>
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.item icon="home" href="{{ route('portal.dashboard', $eventParameters) }}" :current="request()->routeIs('portal.dashboard')" wire:navigate>
                Home
            </flux:sidebar.item>

            @if ($hasAthleteRegistrations)
                <flux:sidebar.item icon="trophy" href="{{ route('portal.participations', $eventParameters) }}" :current="request()->routeIs('portal.participations')" wire:navigate>
                    Teilnahmen
                </flux:sidebar.item>
            @endif

            <flux:sidebar.item icon="heart" href="{{ route('portal.donations', $eventParameters) }}" :current="request()->routeIs('portal.donations')" wire:navigate>
                Spenden
            </flux:sidebar.item>
        </flux:sidebar.nav>

        <flux:sidebar.spacer />

        <livewire:portal-logout-button />
    </flux:sidebar>

    <flux:main container class="min-h-screen">
        <div class="mb-6 lg:hidden">
            <flux:sidebar.toggle icon="bars-2" inset="left" />
        </div>

        <main>
            @yield('content')
        </main>
    </flux:main>
@endsection
