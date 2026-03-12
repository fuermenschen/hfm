@extends('layouts.public')
@php use Illuminate\Support\Facades\Vite; @endphp
@section('content')

    <div>
        <x-page-title>Fragen und Antworten</x-page-title>
        <div
            class="w-full max-w-2xl mx-auto text-left sm:text-center">Auf dieser Seite findest du alle wichtigen
            Informationen rund um den
            Spendenlauf &laquo;<strong>Höhenmeter für
                Menschen</strong>&raquo;. Sollte dennoch etwas unklar sein,
            <x-inline-link href=" {{ route('contact') }}">schreib uns</x-inline-link>
            !
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-y-3 gap-x-3 sm:gap-y-2 my-12 mx-auto max-w-lg">
            <flux:button
                href="#allgemein"
                variant="filled"
                size="xs"
            >Allgemein</flux:button>
            <flux:button
                href="#sportlerinnen"
                variant="filled"
                size="xs"
            >Sportler:innen</flux:button>
            <flux:button
                href="#spenderinnen"
                variant="filled"
                size="xs"
            >Spender:innen</flux:button>
            <flux:button
                href="#hintergruende"
                variant="filled"
                size="xs"
            >Hintergründe</flux:button>
        </div>
    </div>

    <x-page-subtitle id="allgemein">Allgemein</x-page-subtitle>

    <dl class="space-y-6 divide-y divide-gray-900/10 dark:divide-gray-100/30  mb-9">

        <x-faq-question-answer>
            <x-slot:question>Wann und wo findet der Anlass statt?</x-slot:question>
            <span>Der Spendenlauf findet am <strong>Samstag, 12. September 2026 in Winterthur</strong> statt. Der Anlass
            dauert von <strong>13 Uhr bis 16 Uhr</strong>. Start und Ziel des Rundkurses sind bei der Brühlgut Stiftung
            (Brühlbergstrasse 6).</span>
            <div x-data="{ pointerEvents: false, timeout: null }" @click.outside="pointerEvents = false"
                 @click="pointerEvents = true;"
                 @mouseenter="clearTimeout(timeout); timeout = setTimeout(() => pointerEvents = true, 2000)"
                 @mouseleave="clearTimeout(timeout); timeout = setTimeout(() => pointerEvents = false, 2000)"
                 class="relative w-full h-96 mt-6">
                <iframe
                    :class="pointerEvents? 'pointer-events-auto' : 'pointer-events-none'"
                    src='https://map.geo.admin.ch/embed.html?lang=de&topic=ech&bgLayer=ch.swisstopo.pixelkarte-farbe&layers=ch.bav.haltestellen-oev,KML%7C%7Chttps:%2F%2Fpublic.geo.admin.ch%2Fapi%2Fkml%2Ffiles%2FWa_orMUOTPmuGvtVdPcemw&E=2695929.64&N=1261399.28&zoom=10'
                    class="w-full h-96">
                </iframe>
            </div>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Wie kann man am besten anreisen?</x-slot:question>
            <span>Am besten reist du mit dem öffentlichen Verkehr an. Die Brühlgut Stiftung ist mit dem Bus gut erreichbar
            (
            <x-inline-link
                href="https://www.sbb.ch/de?stops=[{%22label%22%3A%22%22%2C%22type%22%3A%22ID%22%2C%22value%22%3A%22%22}%2C{%22value%22%3A%228576180%22%2C%22type%22%3A%22ID%22%2C%22label%22%3A%22Winterthur%2C%20Loki%22}]"
                target="_blank">Zum Fahrplan
            </x-inline-link>
            ). Wenn du mit dem Auto anreist, gibt es das nahegelegene Parkhaus Lokwerk. Bei
            der Brühlgut Stiftung hat es keine Parkplätze.</span>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Gibt es Verpflegung vor Ort?</x-slot:question>
            Es gibt nur Verpflegung für Sportler:innen. Es wird Getränke (Wasser und isotonisches Getränk) und Snacks geben. Die Verpflegungsstation befindet sich beim
            Start/Ziel des Rundkurses.
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Was passiert bei schlechtem Wetter?</x-slot:question>
            Der Anlass findet bei jedem Wetter statt. Sollte es regnen, empfehlen wir, entsprechende Kleidung
            mitzunehmen. Bei Gewitter oder Sturm kann der Anlass abgesagt werden. Wir informieren dich in diesem Fall
            rechtzeitig.
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Ich brauche für die Teilnahme weitere Unterstützung. An wen kann ich mich wenden?
            </x-slot:question>
            <span>Wir geben unser Bestes, dass alle einen Teil von Höhenmeter für Menschen sein können. Sei es, dass du Unterstützung bei der Teilnahme als Sportler:in hast, dass du eine Unverträglichkeit hast und Fragen zum Essen hast oder sonstige Anliegen hast.</span>
            <span>Bitte <x-inline-link
                    href="{{ route('contact') }}">melde dich</x-inline-link> bei uns, wir finden eine Lösung.</span>
        </x-faq-question-answer>

    </dl>

    <x-page-subtitle id="sportlerinnen">Sportler:innen</x-page-subtitle>


    <dl class="space-y-6 divide-y divide-gray-900/10 dark:divide-gray-100/30  mb-9">

        <x-faq-question-answer>
            <x-slot:question>Wie läuft alles ab?</x-slot:question>
            <span class="mb-6">Der Ablauf für dich als Sportler:in ist wie folgt:</span>
            <ol class="list-decimal text-sm list-inside space-y-3">
                <li>Du überlegst dir, welche:n der drei Benefizpartner:innen du unterstützen möchtest (alle drei auch
                    möglich).
                </li>
                <li>Du registrierst dich über das
                    <x-inline-link href="{{ route('become-athlete') }}">Anmeldeformular</x-inline-link>
                    als Sportler:in.
                </li>
                <li>Wir senden dir einige Flyer und Informationen zu, die du an deine Freunde und Familie weitergeben
                    kannst.
                </li>
                <li>Du suchst Spender:innen für dich. Diese können dich pro Runde unterstützen.</li>
                <li>Am Anlass läufst oder fährst du so viele Runden wie möglich.</li>
                <li>Fertig! Den Rest übernehmen wir.</li>
            </ol>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Wann startet der Lauf?</x-slot:question>
            Es gibt um <strong>13 Uhr</strong> einen gemeinsamen Start für alle Sportler:innen.
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Wie verläuft der Rundkurs?</x-slot:question>
            <span>
                Der Rundkurs führt durch das Brühlberg-Quartier in Winterthur. Die Strecke ist
            <strong>1.75&nbsp;km</strong> lang, weist <strong>50 Höhenmeter</strong> auf und ist – bis auf ein kurzes Stück durch den Brühlgutpark – vollständig asphaltiert.
                Das unbefestigte Teilstück kann bei Bedarf über die Waldhofstrasse, Zürcherstrasse und zurück zur Brühlbergstrasse umfahren werden.
                Der Anstieg hat eine <strong>Steigung von bis zu 11%</strong>. Start und Ziel sind bei der Brühlgut Stiftung; von dort verläuft die Strecke im Uhrzeigersinn durch das Quartier.
            </span>
            <span>
                Du kannst die Strecke auch <x-inline-link href="https://s.geo.admin.ch/yb9swnrqvtal"
                                                          target="_blank">online ansehen</x-inline-link>, <x-inline-link
                    href="{{ Vite::asset('resources/files/hfm_strecke.pdf') }}"
                    download target="_blank">als PDF herunterladen</x-inline-link> oder <x-inline-link
                    href="{{ Vite::asset('resources/files/hfm_strecke.gpx') }}"
                    download target="_blank">als GPX-Datei herunterladen</x-inline-link>.
            </span>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Welche Sportarten sind geeignet?</x-slot:question>
            <span>Am besten geeignet sind wohl Laufen und Velofahren. Aber es ist grundsätzlich alles erlaubt. Bitte beachte,
            dass es Steigungen von bis zu 11% hat.</span>
            <span>Wer die Strecke aus eigener Kraft zurücklegen kann, soll dies auch tun. Wer dazu nicht in der Lage ist und Hilfsmittel oder Hilfspersonen benötigt, darf dies.</span>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Kann ich etwas gewinnen?</x-slot:question>
            <span>Ja, es gibt verschiedene Preise zu gewinnen, die wir von unseren <x-inline-link
                    href=" {{ route('home').'#sponsors' }} ">Sponsor:innen</x-inline-link> haben. Diese werden an die Sportler:innen vergeben, welche am meisten Spenden sammeln konnten.
            </span>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Darf ich mit dem Elektrovelo oder Elektroscooter kommen?</x-slot:question>
            Nein, grundsätzlich soll die Strecke aus eigener Kraft zurückgelegt werden. Ausgenommen hiervon sind
            Sportler:innen, denen es nicht möglich ist, die Strecke ohne Hilfsmittel oder Begleitpersonen zurückzulegen.
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Ich bin nicht besonders sportlich. Kann ich trotzdem teilnehmen?</x-slot:question>
            Ja, der Anlass ist für alle geeignet. Du kannst so viele Runden laufen oder fahren, wie du möchtest. Es
            geht nicht darum, wer am besten ist, sondern darum, gemeinsam Spenden zu sammeln.
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Wie ist der Ablauf am Anlass?</x-slot:question>
            <ul class="list-disc text-sm list-inside space-y-3">
                <li>
                    Ab <strong>12:00 Uhr</strong> sind die Startnummern im Rundenbüro abholbereit. Das Rundenbüro befindet sich gut sichtbar beim Start/Ziel, direkt vor der Brühlgut Stiftung.
                </li>
                <li>
                    Die Startnummer muss gut sichtbar auf der Vorderseite deines Trikots befestigt werden. Es stehen dafür Sicherheitsnadeln bereit.
                </li>
                <li>
                    Um <strong>13:00 Uhr</strong> gibt es einen gemeinsamen Start mit allen Sportler:innen.
                </li>
                <li>
                    Um <strong>16:00 Uhr</strong> gibt es einen gemeinsamen Abschluss des Laufs.
                </li>
                <li>
                    Falls du wirklich nicht um 13 Uhr starten kannst, finden wir eine Lösung. Melde dich bitte vorgängig bei uns.
                </li>
                <li>
                    Wir zählen deine Runden. Es hilft, wenn du ebenfalls mitzählst.
                </li>
            </ul>
        </x-faq-question-answer>

    </dl>

    <x-page-subtitle id="spenderinnen">Spender:innen</x-page-subtitle>
    <dl class="space-y-6 divide-y divide-gray-900/10 dark:divide-gray-100/30 mb-9">

        <x-faq-question-answer>
            <x-slot:question>Wie läuft alles ab?</x-slot:question>
            <span class="mb-6">Der
                Ablauf für dich als Spender:in ist wie folgt:</span>
            <ol class="list-decimal text-sm list-inside space-y-3">
                <li>Du überlegst dir, welche:n Sportler:in du unterstützen möchtest.</li>
                <li>Du überlegst dir, welchen Betrag du pro Runde spenden möchtest.</li>
                <li>Du meldest dich über den Newsletter an und wir informieren dich, sobald das Spendenformular wieder offen ist.</li>
                <li>Du feuerst die Sportler:innen kräftig an am 12. September 2026.</li>
                <li>Wir senden dir eine Rechnung mit einem Einzahlungsschein zu.</li>
                <li>Fertig! Vielen Dank für deine Unterstützung.</li>
            </ol>

        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Wie kann ich meine Spende bezahlen?</x-slot:question>
            Du bekommst nach dem Anlass eine Rechnung mit einem Einzahlungsschein von uns. Die Rechnung wird
            entsprechend der zurückgelegten Runden
            ausgestellt.
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Wie kann ich meine Spende von den Steuern abziehen?</x-slot:question>
            Da der Verein für Menschen eine gemeinnützige Organisation ist, kannst du deine Spende von den Steuern
            abziehen.
            Die Beilage der Rechnung sollte dafür reichen.
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>An wen gehen die Spenden?</x-slot:question>
            Die Spenden gehen an die Benefizpartner:innen. Aktuell bestätigt ist:
            <ul role="list" class="mt-8 space-y-8 text-sm">
                <li class="flex gap-x-3">
                            <span>
                                <strong class="font-semibold"> Brühlgut Stiftung </strong> <br> Die Brühlgut Stiftung begleitet und fördert Menschen mit Beeinträchtigung.
                                <x-inline-link href="https://www.xn--brhlgut-o2a.ch/"
                                               target="_blank">Brühlgut Stiftung</x-inline-link>
                            </span>
                </li>
                <li class="flex gap-x-3">
                            <span>
                                <strong class="font-semibold"> Weiterer Benefizpartner </strong> <br> Information folgt.
                            </span>
                </li>
                <li class="flex gap-x-3">
                            <span>
                                <strong class="font-semibold"> Weiterer Benefizpartner </strong> <br> Information folgt.
                            </span>
                </li>
            </ul>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Wie viel von meiner Spende kommt bei den Benefizpartner:innen an?</x-slot:question>
            <span><strong>100% deiner Spende kommt bei den Benefizpartner:innen an.</strong> Der Verein für Menschen übernimmt die
            gesamten
            Kosten des Anlasses.</span>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Wie viel soll ich spenden?</x-slot:question>
            Das ist dir überlassen, jeder Betrag ist willkommen. Du kannst einen Betrag pro Runde und auch
            Mindest- oder Maximalbeträge festlegen. Viele Spender:innen geben 5-10
            Franken pro Runde.
        </x-faq-question-answer>

    </dl>

    <x-page-subtitle id="hintergruende">Hintergründe</x-page-subtitle>
    <dl class="space-y-6 divide-y divide-gray-900/10 dark:divide-gray-100/30 ">

        <x-faq-question-answer>
            <x-slot:question>Weshalb heisst der Anlass Höhenmeter für Menschen?</x-slot:question>
            <span>
                Für manche Menschen fühlt sich jeder einzelne Tag an, als müssten sie Berge erklimmen. Mit dem Anlass
                „Höhenmeter für Menschen” möchten wir versuchen, dieses Gefühl nachvollziehen und dabei Geld für
                Organisationen sammeln, welche diese Menschen unterstützen und begleiten.
            </span>

            <span>
                Wir erklimmen Höhenmeter, um die täglichen Anstrengungen und Hindernisse zu symbolisieren, die viele
                Menschen überwinden müssen. Gemeinsam setzen wir ein Zeichen der Solidarität und Unterstützung.
            </span>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Wie wurden die Benefizpartner:innen ausgewählt?</x-slot:question>
            Bei der Auswahl der Benefizpartner:innen haben wir darauf geachtet, dass nur Institutionen ausgewählt
            werden,
            die in Winterthur und Umgebung tätig sind. Zudem haben wir uns auf Institutionen fokussiert, die sich für
            Menschen einsetzen. Aktuell bestätigt ist:
            <ul class="list-disc text-sm mt-6 list-inside space-y-3">
                <li>
                    <x-inline-link href="https://www.xn--brhlgut-o2a.ch/" target="_blank">Brühlgut Stiftung
                    </x-inline-link>
                </li>
                <li>
                    Weiterer Benefizpartner folgt.
                </li>
                <li>
                    Weiterer Benefizpartner folgt.
                </li>
            </ul>
        </x-faq-question-answer>

    </dl>
@endsection
