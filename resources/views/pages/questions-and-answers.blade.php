@extends('layouts.public')

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
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-y-xs gap-x-xs sm:gap-y-2 my-lg mx-auto max-w-lg">
            <x-button
                href="#allgemein"
                label="Allgemein"
                outline
                dark
                sm
            />
            <x-button
                href="#sportlerinnen"
                label="Sportler:innen"
                outline
                dark
                sm
            />
            <x-button
                href="#spenderinnen"
                label="Spender:innen"
                outline
                dark
                sm
            />
            <x-button
                href="#hintergruende"
                label="Hintergründe"
                outline
                dark
                sm
            />
        </div>
    </div>

    <x-page-subtitle id="allgemein">Allgemein</x-page-subtitle>

    <dl class="space-y-6 divide-y divide-gray-900/10 dark:divide-gray-100/30  mb-md">

        <x-faq-question-answer>
            <x-slot:question>Wann und wo findet der Anlass statt?</x-slot:question>
            <span>Der Spendenlauf findet am <strong>Samstag, 21. September 2024 in Winterthur</strong> statt. Der Anlass
            dauert von <strong>13 Uhr bis 18 Uhr</strong>. Start und Ziel des Rundkurses sind bei der Brühlgut Stiftung
            (Brühlbergstrasse 6).</span>
            <div x-data="{ pointerEvents: false, timeout: null }" @click.outside="pointerEvents = false"
                 @click="pointerEvents = true;"
                 @mouseenter="clearTimeout(timeout); timeout = setTimeout(() => pointerEvents = true, 2000)"
                 @mouseleave="clearTimeout(timeout); timeout = setTimeout(() => pointerEvents = false, 2000)"
                 class="relative w-full h-96 mt-sm">
                <iframe
                    :class="pointerEvents? 'pointer-events-auto' : 'pointer-events-none'"
                    src='https://map.geo.admin.ch/embed.html?lang=de&topic=ech&bgLayer=ch.swisstopo.pixelkarte-farbe&layers=ch.bav.haltestellen-oev,KML%7C%7Chttps:%2F%2Fpublic.geo.admin.ch%2Fapi%2Fkml%2Ffiles%2FWa_orMUOTPmuGvtVdPcemw&E=2695929.64&N=1261399.28&zoom=10'
                    class="w-full h-96">
                </iframe>
            </div>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Wie kann man am besten anreisen?</x-slot:question>
            Am besten reist du mit dem öffentlichen Verkehr an. Die Brühlgut Stiftung ist mit dem Bus gut erreichbar
            (Haltestelle Winterthur, Loki). Wenn du mit dem Auto anreist, gibt es das nahegelegene Parkhaus Lokwerk.
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Gibt es Verpflegung vor Ort?</x-slot:question>
            Es wird Getränke und Snacks geben. Für die Sportler:innen gibt es zudem eine Verpflegungsstation bei dem
            Start/Ziel des Rundkurses.
        </x-faq-question-answer>

    </dl>

    <x-page-subtitle id="sportlerinnen">Sportler:innen</x-page-subtitle>


    <dl class="space-y-6 divide-y divide-gray-900/10 dark:divide-gray-100/30  mb-md">

        <x-faq-question-answer>
            <x-slot:question>Wie läuft alles ab?</x-slot:question>
            <span class="mb-sm">Der Ablauf für dich als Sportler:in ist wie folgt:</span>
            <ol class="list-decimal text-sm list-inside space-y-xs">
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
                <li>Am Anlass selber läufst oder fährst du so viele Runden wie möglich.</li>
                <li>Fertig! Den Rest übernehmen wir.</li>
            </ol>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Wie verläuft der Rundkurs?</x-slot:question>
            <x-todo>Kai, bitte liefere hier Eckdaten, Karte und GPX-Datei.</x-todo>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Welche Sportarten sind geeignet?</x-slot:question>
            <span>Am besten geeignet sind wohl Laufen und Velofahren. Aber es ist grundsätzlich alles erlaubt. Bitte beachte,
            dass
            es steile Abschnitte und Kieswege entlang der Strecke hat.</span>
            <span>Wer die Strecke aus eigener Kraft zurücklegen kann, soll dies auch tun. Wer dazu nicht in der Lage ist und Hilfsmittel oder Hilfspersonen benötigt, darf dies.</span>
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

    </dl>

    <x-page-subtitle id="spenderinnen">Spender:innen</x-page-subtitle>
    <dl class="space-y-6 divide-y divide-gray-900/10 dark:divide-gray-100/30 mb-md">

        <x-faq-question-answer>
            <x-slot:question>Wie läuft alles ab?</x-slot:question>
            <span class="mb-sm">Der
                Ablauf für dich als Spender:in ist wie folgt:</span>
            <ol class="list-decimal text-sm list-inside space-y-xs">
                <li>Du überlegst dir, welche:n Sportler:in du unterstützen möchtest.</li>
                <li>Du überlegst dir, welchen Betrag du pro Runde spenden möchtest.</li>
                <li>Du füllst das
                    <x-inline-link href="{{ route('become-donator') }}">Spendenformular</x-inline-link>
                    aus.
                </li>
                <li>Du feuerst die Sportler:innen kräftig an am 21. September 2024.</li>
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
            Wenn du deine Spende von den Steuern abziehen möchtest, gib uns bitte Bescheid. Wir senden dir dann eine
            Spendenbestätigung zu.
            <x-todo>Beni, bitte prüfen, ob wir bedingungen erfüllen.</x-todo>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>An wen gehen die Spenden?</x-slot:question>
            Die Spenden gehen an die drei Benefizpartner:innen. Diese sind:
            <ul class="list-disc text-sm mt-sm list-inside space-y-xs">
                <li>
                    <x-inline-link href="https://bruehlgut.ch" target="_blank">Brühlgut Stiftung</x-inline-link>
                </li>
                <li>
                    <x-inline-link href="https://kinderseele.ch" target="_blank">Institut Kinderseele Schweiz
                    </x-inline-link>
                </li>
                <li>
                    <x-inline-link href="https://143.ch" target="_blank">Dargebotene Hand Winterthur und Schaffhausen
                    </x-inline-link>
                </li>
            </ul>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Wie viel soll ich spenden?</x-slot:question>
            Das ist dir überlassen, jeder Betrag ist willkommen. Du kannst pro Runde einen Betrag festlegen und auch
            Mindest- oder Maximalbeträge festlegen, damit du nicht überrascht wirst. Viele Spender:innen geben 5-10
            Franken pro Runde.
        </x-faq-question-answer>

    </dl>

    <x-page-subtitle id="hintergruende">Hintergründe</x-page-subtitle>
    <dl class="space-y-6 divide-y divide-gray-900/10 dark:divide-gray-100/30 ">

        <x-faq-question-answer>
            <x-slot:question>Was ist der Round Table?</x-slot:question>
            <span>Round Table ist eine internationale Bewegung, die in einzelnen &laquo;Tischen&raquo; (=Vereine) organisiert
            sind. Der <strong> Round Table 25 Winterthur </strong> ist Teil dieser Organisation. Round Table hat zum
            Ziel, lokale und internationale Freundschaften zu fördern, einen Austausch über verschiedene Begrufsgruppen hinweg zu fördern und sich sozial zu engagieren.</span>
            <span>Der Round Table 25 Winterthur besteht seit über 50 Jahren und hat sich in dieser Zeit auf verschiedenste Art und Weise sozial engagieren dürfen. Mit dem Anlass Höhenmeter für Menschen soll ein neues, langjähriges Projekt entstehen.</span>
            <span>Weitere Informationen zum Round Table 25 Winterthur findest du auf <x-inline-link
                    href="https://rt25.ch" target="_blank">auf der Website des RT25 Winterthur</x-inline-link>.</span>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Weshalb heisst der Anlass Höhenmeter für Menschen?</x-slot:question>
            <x-todo>Kai, bitte liefern.</x-todo>
        </x-faq-question-answer>

        <x-faq-question-answer>
            <x-slot:question>Wie wurden die Benefizpartner:innen ausgewählt?</x-slot:question>
            Bei der Auswahl der Benefizpartner:innen haben wir darauf geachtet, dass nur Institutionen ausgewählt
            werden,
            die in Winterthur und Umgebung tätig sind. Zudem haben wir uns auf Institutionen fokussiert, die sich für
            Menschen einsetzen. Die drei Benefizpartner:innen sind:
            <ul class="list-disc text-sm mt-sm list-inside space-y-xs">
                <li>
                    <x-inline-link href="https://bruehlgut.ch" target="_blank">Brühlgut Stiftung</x-inline-link>
                </li>
                <li>
                    <x-inline-link href="https://kinderseele.ch" target="_blank">Institut Kinderseele Schweiz
                    </x-inline-link>
                </li>
                <li>
                    <x-inline-link href="https://143.ch" target="_blank">Dargebotene Hand Winterthur und Schaffhausen
                    </x-inline-link>
                </li>
            </ul>
        </x-faq-question-answer>

    </dl>
@endsection
