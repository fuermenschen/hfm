@php use Illuminate\Support\Facades\Route; @endphp
@extends('layouts.base')

@props(['user'])

@php
    $thisRoute = Route::currentRouteName();

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
            'permissions' => ['admin.settings'],
        ],
        [
            'label' => 'Pulse',
            'route' => 'pulse',
            'icon' => 'signal',
            'current' => $thisRoute === 'pulse',
        ],
        [
            'label' => 'Logs',
            'route' => '/admin/logs',
            'icon' => 'document-text',
            'current' => request()->is('admin/logs*'),
            'target' => '_blank',
        ],
        [
            'label' => 'Ausloggen',
            'route' => 'admin.logout',
            'icon' => 'arrow-right-start-on-rectangle',
            'current' => false,
            'permissions' => [],
        ],
    ];
@endphp

@section('body')
    <div x-data="{ menuOpen: false }" class="bg-base-50 text-base-900 dark:bg-base-950 dark:text-base-100 min-h-screen">
        <!-- Off-canvas menu for mobile, show/hide based on off-canvas menu state. -->
        <div class="relative z-50 lg:hidden" role="dialog" aria-modal="true">
            <!--
              Off-canvas menu backdrop, show/hide based on off-canvas menu state.

              Entering: "transition-opacity ease-linear duration-300"
                From: "opacity-0"
                To: "opacity-100"
              Leaving: "transition-opacity ease-linear duration-300"
                From: "opacity-100"
                To: "opacity-0"
            -->
            <div
                class="bg-base-800/80 fixed inset-0 backdrop-blur-md"
                x-show="menuOpen"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
            ></div>

            <div class="fixed inset-0 flex" x-show="menuOpen">
                <!--
                  Off-canvas menu, show/hide based on off-canvas menu state.

                  Entering: "transition ease-in-out duration-300 transform"
                    From: "-translate-x-full"
                    To: "translate-x-0"
                  Leaving: "transition ease-in-out duration-300 transform"
                    From: "translate-x-0"
                    To: "-translate-x-full"
                -->
                <div
                    class="relative mr-16 flex w-full max-w-xs flex-1"
                    x-show="menuOpen"
                    x-transition:enter="transition ease-in-out duration-300 transform"
                    x-transition:enter-start="-translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in-out duration-300 transform"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="-translate-x-full"
                >
                    <!--
                      Close button, show/hide based on off-canvas menu state.

                      Entering: "ease-in-out duration-300"
                        From: "opacity-0"
                        To: "opacity-100"
                      Leaving: "ease-in-out duration-300"
                        From: "opacity-100"
                        To: "opacity-0"
                    -->
                    <div
                        class="absolute top-0 left-full flex w-16 justify-center pt-5"
                        x-show="menuOpen"
                        x-transition:enter="ease-in-out duration-300"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in-out duration-300"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        @click="menuOpen = false"
                    >
                        <button type="button" class="-m-2.5 p-2.5">
                            <span class="sr-only">Close sidebar</span>
                            <flux:icon.x-mark class="text-base-100 size-6" />
                        </button>
                    </div>

                    <!-- Sidebar component, swap this element with another sidebar if you like -->
                    <div class="bg-base-900 flex grow flex-col gap-y-5 overflow-y-auto px-6 pb-4">
                        <div class="flex h-16 shrink-0 items-center">
                            <img
                                class="h-8 w-auto"
                                src="{{ Vite::asset("resources/images/logo_dark.svg") }}"
                                alt="Your Company"
                            />
                        </div>
                        <x-admin.navigation
                            :mainNavigation="$mainNavigation"
                            :secondaryNavigation="$secondaryNavigation"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            <!-- Sidebar component, swap this element with another sidebar if you like -->
            <div class="bg-base-900 flex grow flex-col gap-y-5 overflow-y-auto px-6 pb-4">
                <div class="flex h-16 shrink-0 items-center">
                    <img
                        class="h-8 w-auto"
                        src="{{ Vite::asset("resources/images/logo_dark.svg") }}"
                        alt="Höhenmeter für Menschen"
                    />
                </div>
                <x-admin.navigation :mainNavigation="$mainNavigation" :secondaryNavigation="$secondaryNavigation" />
            </div>
        </div>

        <div class="min-w-0 lg:pl-72">
            <div class="sticky top-0 z-40 flex h-14 shrink-0 flex-row items-center gap-x-4 px-4 sm:gap-x-6 sm:px-6 lg:hidden">
                <button type="button" class="-m-2.5 p-2.5 lg:hidden" @click="menuOpen = true">
                    <span class="sr-only">Open sidebar</span>
                    <flux:icon.bars-3 class="size-6" />
                </button>
            </div>

            <main class="min-w-0 overflow-x-clip py-10">
                <div class="min-w-0 px-4 sm:px-6 lg:px-8">
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
                </div>
            </main>
        </div>
    </div>
@endsection
