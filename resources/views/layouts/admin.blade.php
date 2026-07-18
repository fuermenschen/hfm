@php use Illuminate\Support\Facades\Route; @endphp
@extends('layouts.base')

@props(["user"])

@php

    $thisRoute = Route::currentRouteName();

        $mainNavigation = [
            [
                'label' => 'Dashboard',
                'route' => 'admin.dashboard',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25" /></svg>',
                'current' => $thisRoute === 'admin.dashboard',
            ],
            [
                'label' => 'Anlässe',
                'route' => 'admin.donation-events.index',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V8.25A2.25 2.25 0 0 1 5.25 6h13.5A2.25 2.25 0 0 1 21 8.25v10.5M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-6.75A2.25 2.25 0 0 1 5.25 9.75h13.5A2.25 2.25 0 0 1 21 12v6.75" /></svg>',
                'current' => $thisRoute === 'admin.donation-events.index',
            ],
            [
                'label' => 'Partner:innen',
                'route' => 'admin.partners.index',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.742-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.036.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.58-5.965-1.584A6.062 6.062 0 0 1 6 18.75m12 0a5.971 5.971 0 0 0-.94-3.197M6 18.75a5.971 5.971 0 0 1 .94-3.197m0 0a3 3 0 0 1 4.682-2.72m-4.682 2.72A9.094 9.094 0 0 1 3 18.72m9-5.969a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm6 3a2.25 2.25 0 1 0-4.5 0 2.25 2.25 0 0 0 4.5 0Zm-13.5 0a2.25 2.25 0 1 0-4.5 0 2.25 2.25 0 0 0 4.5 0Z" /></svg>',
                'current' => $thisRoute === 'admin.partners.index',
            ],
            [
                'label' => 'Sponsor:innen',
                'route' => 'admin.sponsors.index',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688 0-1.24-.56-1.24-1.248v-6.185c0-.69.552-1.248 1.24-1.248h7.232c.689 0 1.248.558 1.248 1.248v6.185c0 .688-.559 1.248-1.248 1.248H10.34Zm-6.716-1.213a.75.75 0 0 1 0-1.5h3.324a.75.75 0 0 1 0 1.5H3.624Zm0-3.31a.75.75 0 0 1 0-1.5h3.324a.75.75 0 0 1 0 1.5H3.624Zm0-3.31a.75.75 0 0 1 0-1.5h3.324a.75.75 0 0 1 0 1.5H3.624ZM11.5 18.75a.75.75 0 0 1 1.5 0V21a.75.75 0 0 1-1.5 0v-2.25Z" /></svg>',
                'current' => $thisRoute === 'admin.sponsors.index',
            ],
            [
                'label' => 'FAQs',
                'route' => 'admin.faqs.index',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a3.375 3.375 0 1 1 6.75 0c0 1.36-.793 2.535-1.941 3.085-.9.432-1.434 1.21-1.434 2.165v.093m.008 3h.008M21 12c0 4.97-4.03 9-9 9a8.96 8.96 0 0 1-4.966-1.494L3 21l1.495-4.034A8.96 8.96 0 0 1 3 12c0-4.97 4.03-9 9-9s9 4.03 9 9Z" /></svg>',
                'current' => $thisRoute === 'admin.faqs.index',
            ],
            [
                'label' => 'Externe Personen',
                'route' => 'admin.external-users.index',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.742-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.036.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.58-5.965-1.584A6.062 6.062 0 0 1 6 18.75m12 0a5.971 5.971 0 0 0-.94-3.197M6 18.75a5.971 5.971 0 0 1 .94-3.197m0 0a3 3 0 0 1 4.682-2.72m-4.682 2.72A9.094 9.094 0 0 1 3 18.72m9-5.969a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm6 3a2.25 2.25 0 1 0-4.5 0 2.25 2.25 0 0 0 4.5 0Zm-13.5 0a2.25 2.25 0 1 0-4.5 0 2.25 2.25 0 0 0 4.5 0Z" /></svg>',
                'current' => $thisRoute === 'admin.external-users.index',
            ],
            [
                'label' => 'Spenden',
                'route' => 'admin.donations.index',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg>',
                'current' => $thisRoute === 'admin.donations.index',
            ],
            [
                'label' => 'Werkzeuge',
                'route' => 'admin.tools',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z" /></svg>',
                'current' => $thisRoute === 'admin.tools',
            ],
            [
                'label' => 'Dateien',
                'route' => 'admin.files.index',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-19.5 0A2.25 2.25 0 0 0 4.5 15h15a2.25 2.25 0 0 0 2.25-2.25m-19.5 0v5.25A2.25 2.25 0 0 0 4.5 20.25h15a2.25 2.25 0 0 0 2.25-2.25v-5.25M6.75 15.75h.008v.008H6.75v-.008Zm3 0h.008v.008H9.75v-.008Z" /></svg>',
                'current' => $thisRoute === 'admin.files.index',
            ],
        ];

        $secondaryNavigation = [
            [
                'label' => 'Zur Homepage',
                'route' => 'home',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>',
                'current' => $thisRoute === 'home',
            ],
            [
                'label' => 'Einstellungen',
                'route' => 'admin.settings',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 0 1 1.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.559.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.894.149c-.424.07-.764.383-.929.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 0 1-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.398.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 0 1-.12-1.45l.527-.737c.25-.35.272-.806.108-1.204-.165-.397-.506-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.108-1.204l-.526-.738a1.125 1.125 0 0 1 .12-1.45l.773-.773a1.125 1.125 0 0 1 1.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>',
                'current' => $thisRoute === 'admin.settings',
                'permissions' => ['admin.settings'],
            ],
            [
                'label' => 'Pulse',
                'route' => 'pulse',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12a.75.75 0 0 1 .75-.75h3.5l2-5.5a.75.75 0 0 1 1.4.05l2.8 9 1.5-4a.75.75 0 0 1 .7-.5H21a.75.75 0 0 1 0 1.5h-3.55l-2.05 5.467a.75.75 0 0 1-1.416-.025l-2.77-8.9-1.67 4.593a.75.75 0 0 1-.704.497H3a.75.75 0 0 1-.75-.75Z" /></svg>',
                'current' => $thisRoute === 'pulse',
            ],
            [
                'label' => 'Logs',
                'route' => '/admin/logs',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6"><path fill-rule="evenodd" d="M4.5 4.875c0-1.035.84-1.875 1.875-1.875h6.379c.498 0 .975.198 1.327.55l3.494 3.494c.352.352.55.829.55 1.327V19.125c0 1.035-.84 1.875-1.875 1.875h-9.875A1.875 1.875 0 0 1 4.5 19.125V4.875Zm11.25 0v3.75h3.75l-3.75-3.75ZM7.5 9.75A.75.75 0 0 1 8.25 9h7.5a.75.75 0 0 1 0 1.5h-7.5A.75.75 0 0 1 7.5 9.75Zm0 3a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Zm.75 2.25a.75.75 0 0 0 0 1.5h5.25a.75.75 0 0 0 0-1.5H8.25Z" clip-rule="evenodd" /></svg>',
                'current' => request()->is('admin/logs*'),
                'target' => '_blank',
            ],
            [
                'label' => 'Ausloggen',
                'route' => 'admin.logout',
                'svg' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15" /></svg>',
                'current' => false,
                'permissions' => [],
            ],
        ];
@endphp

@section('body')
    <div x-data="{ menuOpen: false}" class="min-h-screen bg-base-50 text-base-900 dark:bg-base-950 dark:text-base-100">
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
            <div class="fixed inset-0 bg-base-800/80 backdrop-blur-md"
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
                <div class="relative mr-16 flex w-full max-w-xs flex-1"
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
                    <div class="absolute left-full top-0 flex w-16 justify-center pt-5"
                         x-show="menuOpen"
                         x-transition:enter="ease-in-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in-out duration-300"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         @click="menuOpen = false">
                        <button type="button" class="-m-2.5 p-2.5">
                            <span class="sr-only">Close sidebar</span>
                            <svg class="h-6 w-6 text-base-100" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                 stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Sidebar component, swap this element with another sidebar if you like -->
                    <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-base-900 px-6 pb-4">
                        <div class="flex h-16 shrink-0 items-center">
                            <img class="h-8 w-auto"
                                 src="{{ Vite::asset("resources/images/logo_dark.svg") }}"
                                 alt="Your Company">
                        </div>
                        <x-admin.navigation :mainNavigation="$mainNavigation"
                                            :secondaryNavigation="$secondaryNavigation" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            <!-- Sidebar component, swap this element with another sidebar if you like -->
            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-base-900 px-6 pb-4">
                <div class="flex h-16 shrink-0 items-center">
                    <img class="h-8 w-auto" src="{{ Vite::asset("resources/images/logo_dark.svg") }}"
                         alt="Höhenmeter für Menschen">
                </div>
                <x-admin.navigation :mainNavigation="$mainNavigation" :secondaryNavigation="$secondaryNavigation" />
            </div>
        </div>

        <div class="min-w-0 lg:pl-72">
            <div
                class="sticky top-0 z-40 flex flex-row h-14 shrink-0 items-center gap-x-4 px-4 sm:gap-x-6 sm:px-6 lg:hidden">
                <button type="button" class="-m-2.5 p-2.5 lg:hidden"
                        @click="menuOpen = true">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                         aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>

            <main class="min-w-0 overflow-x-hidden py-10">
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
                            <div class="text-sm font-semibold uppercase tracking-wide">WICHTIGER HINWEIS: Event-Konfiguration unvollständig</div>
                            <div class="mt-1 text-sm">{{ $eventIssueMessage }}</div>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>
@endsection
