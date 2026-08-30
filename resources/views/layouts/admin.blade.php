@php use Illuminate\Support\Facades\Route; @endphp
@extends('layouts.base')

@php
    $thisRoute = Route::currentRouteName();
    $userName = auth()->user()->name;

    $mainNavigation = [
        [
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
            'icon' => 'chart-bar-square',
            'current' => $thisRoute === 'admin.dashboard',
        ],
        [
            'label' => 'Anlässe',
            'route' => 'admin.donation-events.index',
            'icon' => 'calendar-days',
            'current' => str_starts_with((string) $thisRoute, 'admin.donation-events.'),
        ],
        [
            'label' => 'Partner:innen',
            'route' => 'admin.partners.index',
            'icon' => 'user-group',
            'current' => $thisRoute === 'admin.partners.index',
        ],
        [
            'label' => 'Sponsor:innen',
            'route' => 'admin.sponsors.index',
            'icon' => 'building-office-2',
            'current' => $thisRoute === 'admin.sponsors.index',
        ],
        [
            'label' => 'FAQs',
            'route' => 'admin.faqs.index',
            'icon' => 'question-mark-circle',
            'current' => $thisRoute === 'admin.faqs.index',
        ],
        [
            'label' => 'Sportler:innen',
            'route' => 'admin.athletes.index',
            'icon' => 'trophy',
            'current' => $thisRoute === 'admin.athletes.index',
        ],
        [
            'label' => 'Rundenbüro',
            'route' => 'admin.event-management',
            'icon' => 'flag',
            'current' => $thisRoute === 'admin.event-management',
        ],
        [
            'label' => 'Spender:innen',
            'route' => 'admin.donors.index',
            'icon' => 'heart',
            'current' => $thisRoute === 'admin.donors.index',
        ],
        [
            'label' => 'Spenden',
            'route' => 'admin.donations.index',
            'icon' => 'banknotes',
            'current' => $thisRoute === 'admin.donations.index',
        ],
        [
            'label' => 'Werkzeuge',
            'route' => 'admin.tools',
            'icon' => 'wrench-screwdriver',
            'current' => $thisRoute === 'admin.tools',
        ],
        [
            'label' => 'Dateien',
            'route' => 'admin.files.index',
            'icon' => 'folder',
            'current' => $thisRoute === 'admin.files.index',
        ],
    ];

    $secondaryNavigation = [
        [
            'label' => 'Zur Homepage',
            'route' => 'home',
            'icon' => 'home',
            'current' => $thisRoute === 'home',
        ],
        [
            'label' => 'Einstellungen',
            'route' => 'admin.settings',
            'icon' => 'cog-6-tooth',
            'current' => $thisRoute === 'admin.settings',
        ],
        [
            'label' => 'Pulse',
            'route' => 'pulse',
            'icon' => 'signal',
            'current' => $thisRoute === 'pulse',
            'target' => '_blank',
        ],
        [
            'label' => 'Logs',
            'route' => '/admin/logs',
            'icon' => 'document-text',
            'current' => request()->is('admin/logs*'),
            'target' => '_blank',
        ],
    ];
@endphp

@section('body')
    <div class="admin-shell text-hfm-dark dark:text-hfm-white min-h-screen bg-[#eef3f8] dark:bg-[#0d1a2a]">
        <flux:sidebar
            sticky
            collapsible
            class="dark border-hfm-light/20 bg-hfm-dark text-hfm-white shadow-hfm-dark/20 overflow-x-hidden border-r shadow-xl transition-[width,padding,transform]! duration-200 ease-out"
        >
            <flux:sidebar.header>
                <flux:sidebar.brand :href="route('admin.dashboard')" aria-label="Höhenmeter für Menschen">
                    <x-slot:logo class="h-8! overflow-visible! rounded-none!">
                        <img
                            class="h-8 w-auto in-data-flux-sidebar-collapsed-desktop:hidden"
                            src="{{ Vite::asset('resources/images/logo_dark.svg') }}"
                            alt=""
                        />
                        <span class="bg-hfm-dark text-hfm-white dark:bg-hfm-white dark:text-hfm-dark hidden size-6 items-center justify-center text-sm font-black in-data-flux-sidebar-collapsed-desktop:flex">
                            H
                        </span>
                    </x-slot:logo>
                </flux:sidebar.brand>

                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                @foreach ($mainNavigation as $nav)
                    @php
                        $href = Route::has($nav['route']) ? route($nav['route']) : url($nav['route']);
                        $newTab = ($nav['target'] ?? null) === '_blank';
                    @endphp

                    @if ($newTab)
                        <flux:sidebar.item
                            :icon="$nav['icon']"
                            :href="$href"
                            :current="$nav['current']"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ $nav['label'] }}
                        </flux:sidebar.item>
                    @else
                        <flux:sidebar.item
                            :icon="$nav['icon']"
                            :href="$href"
                            :current="$nav['current']"
                            wire:navigate.hover
                        >
                            {{ $nav['label'] }}
                        </flux:sidebar.item>
                    @endif
                @endforeach
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            <flux:sidebar.nav>
                @foreach ($secondaryNavigation as $nav)
                    @php
                        $href = Route::has($nav['route']) ? route($nav['route']) : url($nav['route']);
                        $newTab = ($nav['target'] ?? null) === '_blank';
                    @endphp

                    @if ($newTab)
                        <flux:sidebar.item
                            :icon="$nav['icon']"
                            :href="$href"
                            :current="$nav['current']"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {{ $nav['label'] }}
                        </flux:sidebar.item>
                    @else
                        <flux:sidebar.item
                            :icon="$nav['icon']"
                            :href="$href"
                            :current="$nav['current']"
                            wire:navigate.hover
                        >
                            {{ $nav['label'] }}
                        </flux:sidebar.item>
                    @endif
                @endforeach
            </flux:sidebar.nav>

            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:sidebar.profile :name="$userName" />

                <flux:menu>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <flux:menu.item type="submit" icon="arrow-right-start-on-rectangle">Ausloggen</flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <flux:header class="dark border-hfm-light/20 bg-hfm-dark text-hfm-white shadow-hfm-dark/20 border-b shadow-md lg:hidden">
            <flux:sidebar.toggle icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile :name="$userName" />

                <flux:menu>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <flux:menu.item type="submit" icon="arrow-right-start-on-rectangle">Ausloggen</flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main class="min-w-0 overflow-x-clip">
            <x-admin.page-title>{{ $title }}</x-admin.page-title>

            @if (($currentDonationEventIssue ?? null) !== null)
                @php
                    $eventIssueMessage = match ($currentDonationEventIssue) {
                        'missing_events_table' => 'Die Tabelle donation_events fehlt. Event-Inhalte auf öffentlichen Seiten bleiben leer.',
                        'missing_current_event' => 'Kein aktueller Anlass definiert. Bitte in den Einstellungen den aktuellen Anlass setzen.',
                        'current_event_not_found' => 'Der konfigurierte aktuelle Anlass existiert nicht mehr. Bitte den aktuellen Anlass neu wählen.',
                        'current_event_unpublished' => 'Der konfigurierte aktuelle Anlass ist nicht veröffentlicht. Öffentliche Event-Inhalte bleiben leer, bis der Anlass veröffentlicht ist.',
                        default => 'Der aktuelle Anlass ist nicht korrekt konfiguriert. Öffentliche Event-Inhalte bleiben leer.',
                    };
                @endphp

                <div class="mb-6 rounded-lg border-2 border-red-500 bg-red-50 p-4 text-red-900 shadow-sm dark:border-red-400 dark:bg-red-950/40 dark:text-red-100">
                    <div class="text-sm font-semibold tracking-wide uppercase">
                        WICHTIGER HINWEIS: Event-Konfiguration unvollständig
                    </div>
                    <div class="mt-1 text-sm">{{ $eventIssueMessage }}</div>
                </div>
            @endif

            @yield('content')
        </flux:main>
    </div>
@endsection
