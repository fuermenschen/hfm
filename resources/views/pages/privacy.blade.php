@extends('layouts.public')

@section('content')
    @component('components.page-title')
        Datenschutz
    @endcomponent

    <p>Der Schutz deiner Daten ist uns wichtig. Und das ist keine Floskel, sondern meinen wir ernst. Wir versuchen, nur
        Daten zu
        erfassen, die wir für die Organisation des Anlasses benötigen. Das sind die folgenden:</p>

    <x-page-subtitle>Allgemeine:r Websitenbenutzer:in</x-page-subtitle>
    <p>Wenn du unsere Website besuchst, erfassen wir keine Daten von dir. Wir verwenden keine Cookies und erfassen keine
        IP-Adressen. Wir verwenden auch keine Analysetools, um das Verhalten der Besucher:innen zu analysieren.</p>

    <x-page-subtitle>Sportler:innen</x-page-subtitle>
    Wir erfassen von dir personenbezogene Daten, die du uns bei der Anmeldung zum Anlass gibst. Das sind:
    <ul class="list-disc m-sm">
        <li>Name</li>
        <li>Vorname</li>
        <li>Volljährigkeit</li>
        <li>Adresse</li>
        <li>E-Mail-Adresse</li>
        <li>Telefonnummer</li>
        <li>Volljährigkeit</li>
    </ul>

    <p>Bei der Anmeldung der Spender:innen wird dein Name angezeigt, sodass die Spender:innen dich auswählen können (Nur
        Initiale des Nachnamens, z.B. Francesca A.). Die anderen Angaben brauchen wir, damit wir dir ein Starter-Paket
        senden können und, falls du noch nicht volljährig bist, deine Eltern über deine Anmeldung informieren können.
        Sobald der Anlass und dessen Nachbearbeitung abgeschlossen sind, werden alle personenbezogenen Daten
        gelöscht.</p>

    <x-page-subtitle>Spender:innen</x-page-subtitle>
    <p>Wir erfassen von dir personenbezogene Daten, die du uns bei der Anmeldung als Spender:in gibst. Das sind:</p>
    <ul class="list-disc m-sm">
        <li>Name</li>
        <li>Vorname</li>
        <li>Adresse</li>
        <li>E-Mail-Adresse</li>
        <li>Telefonnummer</li>
    </ul>

    <p> Dein Name (Nur Vorname und Initiale des Nachnamens, z.B. Francesca A.) ist für die:den Sportler:in, welche du
        ausgewählt hast, sichtbar. Die anderen Angaben brauchen wir, damit wir dich erreichen können und dir nach dem
        Anlass eine Rechnung zustellen können.
        Sobald der Anlass und dessen Nachbearbeitung abgeschlossen sind, werden alle personenbezogenen Daten
        gelöscht.</p>

    <x-page-subtitle>Verantwortliche:r für die Datenverarbeitung</x-page-subtitle>
    <p>Verantwortlich für die Datenverarbeitung ist der Verein <strong>Round Table 25 Winterthur</strong>. Bei Fragen
        zur
        Datenverarbeitung kannst du dich an folgende Adresse wenden:</p>
    <div class="m-sm">
        Verein für Menschen<br>
        <a href="mailto:info@fuer-menschen.ch">info@fuer-menschen.ch</a>
    </div>

@endsection
