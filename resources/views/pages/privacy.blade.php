@extends('layouts.public')

@section('content')
    @component('components.page-title')
        Datenschutz
    @endcomponent

    <p>Der Schutz deiner Daten ist uns wichtig. Und das ist keine Floskel, sondern meinen wir ernst. Wir versuchen, nur
        Daten zu
        erfassen, die wir für die Organisation des Anlasses und unsere Vereinstätigkeit benötigen. Wir geben deine
        Personendaten niemals weiter.</p>

    <x-page-subtitle>Allgemeine:r Websitenbenutzer:in</x-page-subtitle>
    <p>Wenn du unsere Website besuchst, erfassen wir keine Daten von dir. Wir verwenden keine Tracking-Cookies und
        erfassen keine
        IP-Adressen. Wir verwenden auch keine Analysetools, um das Verhalten der Besucher:innen zu analysieren.
        Allerdings ist es technisch unmöglich, dass deine IP-Adresse nicht an unseren Webserver gesendet wird
        (siehe
        <x-inline-link href="https://de.wikipedia.org/wiki/Transmission_Control_Protocol" target="_blank">TCP
        </x-inline-link>
        ). Ob, wie und wie lange unser Hosting Anbieter
        <x-inline-link href="https://www.infomaniak.com/de" target="_blank">Infomaniak</x-inline-link>
        diese IP-Adresse speichert, können wir nicht beeinflussen.
    </p>

    <x-page-subtitle>Sportler:innen</x-page-subtitle>
    <p>Wir erfassen von dir Personendaten, die du uns bei der Anmeldung zum Anlass gibst.</p>

    <p>Bei der Anmeldung der Spender:innen wird dein Name angezeigt, sodass die Spender:innen dich auswählen können (Nur
        Initiale des Nachnamens, z.B. Francesca A.). Die anderen Angaben brauchen wir, damit wir dir ein Starter-Paket
        senden können und, falls du noch nicht volljährig bist, deine Eltern über deine Anmeldung informieren können.
        Sobald der Anlass und dessen Nachbearbeitung abgeschlossen sind, werden alle personenbezogenen Daten
        gelöscht. Ausgenommen hiervon ist dein Name und deine E-Mail-Adresse. Diese behalten wir, um dich über weitere
        Anlässe oder Angebote von uns zu informieren.</p>

    <x-page-subtitle>Spender:innen</x-page-subtitle>
    <p>Wir erfassen von dir personenbezogene Daten, die du uns bei der Anmeldung als Spender:in gibst.</p>

    <p> Dein Name (Nur Vorname und Initiale des Nachnamens, z.B. Francesca A.) ist für die:den Sportler:in, welche du
        ausgewählt hast, sichtbar. Die anderen Angaben brauchen wir, damit wir dich erreichen können und dir nach dem
        Anlass eine Rechnung zustellen können.
        Sobald der Anlass und dessen Nachbearbeitung abgeschlossen sind, werden alle personenbezogenen Daten
        gelöscht. Ausgenommen hiervon ist dein Name und deine E-Mail-Adresse. Diese behalten wir, um dich über weitere
        Anlässe oder Angebote von uns zu informieren. Wenn du eine Spendenrechnung einzahlst, werden Personendaten von
        dir an unsere Bank (ZKB) übermittelt une über mehrere Jahre gespeichert (gesetzliche Fristen). Auf diesem Weg
        haben wir weiterhin Zugriff auf deine Informationen.</p>

    <x-page-subtitle>Vereinsmitglieder</x-page-subtitle>
    <p>Wir erfassen von dir personenbezogene Daten, die du uns bei der Anmeldung als Mitglied gibst.</p>
    <p>Details zum Umgang mit Daten von Vereinsmitgliedern findest du in den Statuten.</p>

    <x-page-subtitle>Verantwortlich für die Datenverarbeitung</x-page-subtitle>
    <p>Verantwortlich für die Datenverarbeitung ist der <strong>Verein für Menschen</strong>. Bei Fragen
        zur
        Datenverarbeitung kannst du dich an folgende Adresse wenden:</p>
    <div class="m-sm">
        Verein für Menschen<br>
        <a href="mailto:info@fuer-menschen.ch">info@fuer-menschen.ch</a>
    </div>

@endsection
