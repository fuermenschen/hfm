@extends('layouts.base')

@section('body')
    <flux:sidebar sticky collapsible="mobile" class="border-r border-hfm-light/40 bg-hfm-white dark:border-slate-700 dark:bg-slate-900">
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
            <flux:sidebar.item icon="home" class="text-base [&_[data-content]]:text-base" href="{{ route('portal.dashboard', $eventParameters) }}" :current="request()->routeIs('portal.dashboard')" wire:navigate>
                Übersicht
            </flux:sidebar.item>

            @if ($hasAthleteRegistrations)
                <flux:sidebar.item icon="trophy" class="text-base [&_[data-content]]:text-base" href="{{ route('portal.participations', $eventParameters) }}" :current="request()->routeIs('portal.participations')" wire:navigate>
                    Teilnahmen
                </flux:sidebar.item>
            @endif

            <flux:sidebar.item icon="heart" class="text-base [&_[data-content]]:text-base" href="{{ route('portal.donations', $eventParameters) }}" :current="request()->routeIs('portal.donations')" wire:navigate>
                Spenden
            </flux:sidebar.item>
        </flux:sidebar.nav>

        <flux:sidebar.spacer />

        <flux:sidebar.nav>
            <flux:sidebar.item icon="user-circle" class="text-base [&_[data-content]]:text-base" href="{{ route('portal.profile', $eventParameters) }}" :current="request()->routeIs('portal.profile')" wire:navigate>
                Profil
            </flux:sidebar.item>
            <flux:sidebar.item icon="question-mark-circle" class="text-base [&_[data-content]]:text-base" href="{{ route('contact') }}" wire:navigate>
                Hilfe & Kontakt
            </flux:sidebar.item>
            <flux:sidebar.item icon="arrow-top-right-on-square" class="text-base [&_[data-content]]:text-base" href="{{ route('home') }}" wire:navigate>
                Zur Website
            </flux:sidebar.item>
        </flux:sidebar.nav>

        <flux:separator variant="subtle" class="my-3" />

        <livewire:portal-logout-button />
    </flux:sidebar>

    <flux:main class="min-h-screen bg-gradient-to-br from-hfm-light/15 via-hfm-white to-white pb-24 dark:from-slate-950 dark:via-slate-900 dark:to-hfm-dark lg:pb-8">
        <div class="mx-auto w-full max-w-6xl">
        <div class="mb-6 flex items-center justify-between lg:hidden">
            <flux:sidebar.toggle icon="bars-2" inset="left" />
        </div>

        <main>
            @yield('content')
        </main>
        </div>
    </flux:main>

    <nav aria-label="Portal-Navigation" class="fixed inset-x-3 bottom-3 z-10 rounded-xl border border-hfm-light/40 bg-hfm-white p-2 shadow-lg shadow-hfm-dark/10 dark:border-slate-700 dark:bg-slate-900 lg:hidden">
        <div class="grid {{ $hasAthleteRegistrations ? 'grid-cols-3' : 'grid-cols-2' }} gap-1">
            <a href="{{ route('portal.dashboard', $eventParameters) }}" wire:navigate aria-current="{{ request()->routeIs('portal.dashboard') ? 'page' : 'false' }}" @class(['flex min-h-14 flex-col items-center justify-center gap-1 rounded-lg px-2 text-xs font-medium transition-colors motion-reduce:transition-none', 'bg-hfm-red text-hfm-white' => request()->routeIs('portal.dashboard'), 'text-slate-500 hover:bg-hfm-light/20 hover:text-hfm-dark dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white' => ! request()->routeIs('portal.dashboard')])>
                <flux:icon.home class="size-5" />
                Übersicht
            </a>
            @if ($hasAthleteRegistrations)
                <a href="{{ route('portal.participations', $eventParameters) }}" wire:navigate aria-current="{{ request()->routeIs('portal.participations') ? 'page' : 'false' }}" @class(['flex min-h-14 flex-col items-center justify-center gap-1 rounded-lg px-2 text-xs font-medium transition-colors motion-reduce:transition-none', 'bg-hfm-red text-hfm-white' => request()->routeIs('portal.participations'), 'text-slate-500 hover:bg-hfm-light/20 hover:text-hfm-dark dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white' => ! request()->routeIs('portal.participations')])>
                    <flux:icon.trophy class="size-5" />
                    Teilnahmen
                </a>
            @endif
            <a href="{{ route('portal.donations', $eventParameters) }}" wire:navigate aria-current="{{ request()->routeIs('portal.donations') ? 'page' : 'false' }}" @class(['flex min-h-14 flex-col items-center justify-center gap-1 rounded-lg px-2 text-xs font-medium transition-colors motion-reduce:transition-none', 'bg-hfm-red text-hfm-white' => request()->routeIs('portal.donations'), 'text-slate-500 hover:bg-hfm-light/20 hover:text-hfm-dark dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white' => ! request()->routeIs('portal.donations')])>
                <flux:icon.heart class="size-5" />
                Spenden
            </a>
        </div>
    </nav>
@endsection
