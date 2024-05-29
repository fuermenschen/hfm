@extends('layouts.public')

@section('content')

    <div>
        @component('components.page-title')
            Fragen und Antworten
        @endcomponent
        Auf dieser Seite findest du Antworten auf häufig gestellte Fragen zum Spendenlauf.

        @component('components.page-subtitle')
            Allgemein
        @endcomponent

        <dl class="space-y-6 divide-y divide-gray-900/10">
            <div class="pt-6" x-data="{ open: false }">
                <dt>
                    <!-- Expand/collapse question button -->
                    <button type="button" class="flex w-full items-start justify-between text-left text-gray-900">
                        <span
                            class="text-base font-semibold leading-7" @click="open = !open">Wann und wo findet der Anlass statt?</span>
                        <span class="ml-6 flex h-7 items-center">
                <!--
                  Icon when question is collapsed.

                  Item expanded: "hidden", Item collapsed: ""
                -->
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                     @click="open = true" x-show="!open">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                </svg>
                            <!--
                              Icon when question is expanded.

                              Item expanded: "", Item collapsed: "hidden"
                            -->
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                     @click="open = false" x-show="open">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                </svg>
              </span>
                    </button>
                </dt>
                <dd class="mt-2 pr-12" id="faq-0" x-show="open" x-transition.scale>
                    <p class="text-base leading-7 text-gray-600">
                        Der Spendenlauf findet am Samstag, den 21. September 2024, in Winterthur statt. Der Anlass
                        dauert
                        von 13:00 - 18:00. Während dieser Zeit können die Teilnehmer den Rundkurs so oft wie möglich
                        zurücklegen. Der Rundkurs liegt im Brühlberg Quartier und die Brühlgut Stiftung dient als
                        Start/Ziel und Informationsstand.
                    </p>
                    <iframe
                        src='https://map.geo.admin.ch/embed.html?lang=de&topic=ech&bgLayer=ch.swisstopo.pixelkarte-farbe&layers=ch.bav.haltestellen-oev,KML%7C%7Chttps:%2F%2Fpublic.geo.admin.ch%2Fapi%2Fkml%2Ffiles%2FWa_orMUOTPmuGvtVdPcemw&E=2695929.64&N=1261399.28&zoom=10'
                        class="w-full h-96 max-w-2xl mt-md"></iframe>
                </dd>
            </div>

            <div class="pt-6" x-data="{ open: false }">
                <dt>
                    <!-- Expand/collapse question button -->
                    <button type="button" class="flex w-full items-start justify-between text-left text-gray-900">
                        <span
                            class="text-base font-semibold leading-7" @click="open = !open">Weshalb heisst der Anlasst Höhenmeter für Menschen?</span>
                        <span class="ml-6 flex h-7 items-center">
                <!--
                  Icon when question is collapsed.

                  Item expanded: "hidden", Item collapsed: ""
                -->
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                     @click="open = true" x-show="!open">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                </svg>
                            <!--
                              Icon when question is expanded.

                              Item expanded: "", Item collapsed: "hidden"
                            -->
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                     @click="open = false" x-show="open">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                </svg>
              </span>
                    </button>
                </dt>
                <dd class="mt-2 pr-12" id="faq-0" x-show="open" x-transition.scale>
                    <p class="text-base leading-7 text-gray-600">
                        Manchmal legt einem das Leben Berge in den Weg, die man nicht von alleine überwinden kann. Der
                        Spendenlauf soll symbolisch für die Höhen und Tiefen im Leben stehen und Menschen unterstützen,
                        die in schwierigen Lebenssituationen stecken.
                    </p>
                </dd>
            </div>

            <!-- More questions... -->
        </dl>

        @component('components.page-subtitle')
            Sportler:innen
        @endcomponent
        <dl class="space-y-6 divide-y divide-gray-900/10">
            <div class="pt-6" x-data="{ open: false }">
                <dt>
                    <!-- Expand/collapse question button -->
                    <button type="button" class="flex w-full items-start justify-between text-left text-gray-900">
                        <span
                            class="text-base font-semibold leading-7"
                            @click="open = !open">Wo führt die Strecke durch?</span>
                        <span class="ml-6 flex h-7 items-center">
                <!--
                  Icon when question is collapsed.

                  Item expanded: "hidden", Item collapsed: ""
                -->
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                     @click="open = true" x-show="!open">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                </svg>
                            <!--
                              Icon when question is expanded.

                              Item expanded: "", Item collapsed: "hidden"
                            -->
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                     @click="open = false" x-show="open">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                </svg>
              </span>
                    </button>
                </dt>
                <dd class="mt-2 pr-12" id="faq-0" x-show="open" x-transition.scale>
                    <p class="text-base leading-7 text-gray-600">
                        Der Spendenlauf findet am Samstag, den 21. September 2024, in Winterthur statt. Der Anlass
                        dauert
                        von 13:00 - 18:00. Während dieser Zeit können die Teilnehmer den Rundkurs so oft wie möglich
                        zurücklegen. Der Rundkurs liegt im Brühlberg Quartier und die Brühlgut Stiftung dient als
                        Start/Ziel und Informationsstand.
                    </p>
                    <iframe
                        src='https://map.geo.admin.ch/embed.html?lang=de&topic=ech&bgLayer=ch.swisstopo.pixelkarte-farbe&layers=ch.bav.haltestellen-oev,KML%7C%7Chttps:%2F%2Fpublic.geo.admin.ch%2Fapi%2Fkml%2Ffiles%2FWa_orMUOTPmuGvtVdPcemw&E=2695929.64&N=1261399.28&zoom=10'
                        class="w-full h-96 max-w-2xl mt-md"></iframe>
                </dd>
            </div>

            <div class="pt-6" x-data="{ open: false }">
                <dt>
                    <!-- Expand/collapse question button -->
                    <button type="button" class="flex w-full items-start justify-between text-left text-gray-900">
                        <span
                            class="text-base font-semibold leading-7"
                            @click="open = !open">Wie sammle ich Spenden?</span>
                        <span class="ml-6 flex h-7 items-center">
                <!--
                  Icon when question is collapsed.

                  Item expanded: "hidden", Item collapsed: ""
                -->
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                     @click="open = true" x-show="!open">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                </svg>
                            <!--
                              Icon when question is expanded.

                              Item expanded: "", Item collapsed: "hidden"
                            -->
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                     @click="open = false" x-show="open">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H6" />
                </svg>
              </span>
                    </button>
                </dt>
                <dd class="mt-2 pr-12" id="faq-0" x-show="open" x-transition.scale>
                    <p class="text-base leading-7 text-gray-600">
                        Manchmal legt einem das Leben Berge in den Weg, die man nicht von alleine überwinden kann. Der
                        Spendenlauf soll symbolisch für die Höhen und Tiefen im Leben stehen und Menschen unterstützen,
                        die in schwierigen Lebenssituationen stecken.
                    </p>
                </dd>
            </div>

            <!-- More questions... -->
        </dl>

    </div>


    {{--<x-page-subtitle>
        Location
    </x-page-subtitle>

    Brühlgut Stiftung, Brühlbergstrasse 6, 8400 Winterthur

    <iframe
        src='https://map.geo.admin.ch/embed.html?lang=de&topic=ech&bgLayer=ch.swisstopo.pixelkarte-farbe&layers=ch.bav.haltestellen-oev,KML%7C%7Chttps:%2F%2Fpublic.geo.admin.ch%2Fapi%2Fkml%2Ffiles%2FWa_orMUOTPmuGvtVdPcemw&E=2695929.64&N=1261399.28&zoom=10'
        class="w-full h-96 max-w-2xl mt-xs"></iframe>

    <x-page-subtitle>
        Ablauf
    </x-page-subtitle>

    Der Sponsorenlauf findet am Samstag, den 21. September 2024, in Winterthur statt. Der Anlass dauert von 13:00 -
    18:00. Während dieser Zeit können die Teilnehmer den Rundkurs so oft wie möglich zurücklegen. Der Rundkurs liegt
    im Brühlberg Quartier und die Brühlgut Stiftung dient als Start/Ziel und Informationsstand.

    <x-page-subtitle>
        Strecke
    </x-page-subtitle>
    Die Strecke befindet sich am Brühlberg in Winterthur. <a
        href="https://drive.google.com/file/d/1-K43Ouj7fAi023aZnDZl8TKH4g8CQO-Y/view">Karte</a>

    <x-page-subtitle>
        Anreise
    </x-page-subtitle>

    Wir empfehlen die Anreise per öffentlichem Verkehr. Vom Bahnhof Winterthur fährt alle paar Minuten ein Bus zur
    Haltestellen <a href="https://maps.app.goo.gl/d6ef7GeKnWW1EwF49">Loki</a>

    <x-page-subtitle>
        Sanitäre Anlagen
    </x-page-subtitle>

    Toiletten befinden sich im Inneren des Gebäudes der Brühlgut Stiftung. Garderoben und Duschen stehen nicht zur
    Verfügung.

    <x-page-subtitle>
        Verpflegung
    </x-page-subtitle>

    Es wird kleinere Snacks wie Sanwiches und Nussgipfel vor Ort geben, da der Event nicht über Mittag stattfindet,
    sind diese allerdings keine vollwertige Mahlzeit.

    <x-page-subtitle>
        Versicherung
    </x-page-subtitle>

    Versicherung ist Sache der Teilnehmenden. Es wird keine Haftung für Unfälle aller Art oder verlorene und
    beschädigte Gegenstände übernommen.
</div>--}}
@endsection
