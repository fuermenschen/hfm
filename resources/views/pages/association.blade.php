@extends('layouts.public')

@section('content')
    @component('components.page-title')
        Verein für Menschen
    @endcomponent

    <div class="w-full max-w-2xl mx-auto text-left sm:text-center flex flex-col space-y-sm">
        <span>Der <strong>Verein für Menschen</strong> ist der Trägerverein des Spendenanlasses <strong>Höhenmeter für
            Menschen</strong>. Er wurde nach der ersten Durchführung des Anlasses <strong>Höhenmeter für
            Menschen</strong> in Winterthur gegründet.</span>
        <span>Auf dieser Seite findest du alle Informationen zu unserem Verein, unsere Statuten und ein <strong>Formular, um
            Mitglied zu werden</strong>.</span>
    </div>
    <div class="flex sm:flex-row flex-col gap-xs my-lg justify-center w-full items-center">
        <flux:button
            href="#hintergruende"
            label="Hintergründe"
            variant="filled"
            icon="information-circle"
            size="xs"
            class="hidden sm:inline-flex"
        >
            Hintergründe
        </flux:button>
        <flux:button
            href="#mitglied-werden"
            icon="user-circle"
            variant="filled"
            class="grow sm:max-w-[200px] w-full"
        >
            Mitglied werden
        </flux:button>
        <flux:button
            href="#dokumente"
            variant="filled"
            icon="document"
            size="xs"
            class="hidden sm:inline-flex"
        >
            Dokumente
        </flux:button>
    </div>

    <x-page-subtitle id="hintergruende">Hintergründe</x-page-subtitle>
    <div class="flex gap-sm flex-col sm:flex-row">
        <div class="flex flex-col space-y-sm">
            <span>
                Im September 2024 wurde der Spendenanlass <strong>Höhenmeter für Menschen</strong> zum ersten Mal in Winterthur
                durchgeführt. Der Anlass war ein voller Erfolg und hat über Fr. 10'000.- an Spendengeldern eingebracht. Diese
                Spenden wurden an die Organisation <strong>Stiftung Kinderseele Schweiz</strong>, <strong>Brühlgut Stiftung Winterthur</strong> und an <strong>Tel. 143 - Die Dargebotene Hand</strong> gespendet.
            </span>
            <span>
                Die Durchführung 2024 wurde durch den Verein <x-inline-link href="www.rt25.ch" target="_blank">RoundTable 25 Winterthur</x-inline-link> organisiert und finanziert. Dies als eines mehrer Sozialprojekte des RoundTable, welcher sich in Winterthur  und Umgebung vielfältig sozial engagiert.
            </span>
        </div>
        <div class="flex flex-col space-y-sm">
            <span>
                Anfangs 2025 wurde der <strong>Verein für Menschen</strong> gegründet, der fortan den <strong>Höhenmeter für Menschen</strong> organisiert. Die Abspaltung von RoundTable 25 Winterthur hatte insbesondere organisatorische Gründe.
            </span>
            <span>
                Der Verein für Menschen ist ein anerkannter, gemeinnütziger Verein, der sich für die Organisation von Spendenanlässen zugunsten von sozialen Projekten in der Schweiz einsetzt. Für den Moment beschränkt sich der Verein auf die Organisation des <strong>Höhenmeter für Menschen</strong> in Winterthur, plant jedoch, dies in Zukunft auch in anderen Städten der Schweiz zu tun.
            </span>
        </div>
    </div>

    <x-page-subtitle id="dokumente">Dokumente</x-page-subtitle>
    <span>
        Hier findest du die Statuten des Vereins für Menschen sowie das Protokoll der Gründungsversammlung.
    </span>
    <div class="flex flex-col gap-sm sm:flex-row mt-sm">
        <flux:button
            href="{{ Vite::asset('resources/files/statuten.pdf') }}"
            target="_blank"
            variant="filled"
            icon-trailing="document-arrow-down"
        >
            Statuten
        </flux:button>
        <flux:button
            href="{{ Vite::asset('resources/files/gruendungsversammlung.pdf') }}"
            target="_blank"
            variant="filled"
            icon-trailing="document-arrow-down"
        >Protokoll Gründungsversammlung
        </flux:button>
    </div>

    <x-page-subtitle id="mitglied-werden">Anmeldeformular Mitgliedschaft</x-page-subtitle>
    Du möchtest Mitglied beim <strong>Verein für
        Menschen</strong> werden? Mega! Du kannst dich hier anmelden und dich dann auf die Art engangieren, die du möchtest. Oder du bleibst nur im Hintergrund und unterstützt uns mit deinem Jahresbeitrag. Jede Art ist sehr willkommen. Wir freuen uns!

    @livewire('become-member-form')

@endsection
