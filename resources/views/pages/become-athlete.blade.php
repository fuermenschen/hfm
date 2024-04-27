@extends('layouts.public')

@section('content')
    <div>
        @component('components.page-title')
            Sportler:in werden
        @endcomponent
        Dein Einsatz für ein Lächeln – Lauf, fahre, rolle für Hilfe, die zu 100% ankommt! Wir laden dich herzlich ein,
        Teil
        dieses besonderen Sponsorenlaufs zu sein, bei dem deine Leidenschaft für Sport mit einem guten Zweck verbunden
        wird.
        Du legst die Strecke im Brühlbergquartier so oft zurück wie möglich und sammelst dabei für jede Runde
        Spendengelder
        deiner Sponsoren für den Benefizpartner deiner Wahl. Du kannst selbst bestimmen, wie du die Strecke zurücklegst,
        wichtig ist nur, dass du die Strecke mit eigener Kraft zurücklegst. Mögliche Optionen sind Joggen, Gehen,
        Fahrradfahren, Skaten oder die Strecke mit dem Rollstuhl zurücklegen.
        Alle sind willkommen: Egal ob Profi oder Gelegenheitssportler, Jung oder Alt, Laufend, gehend, auf dem Fahrrad
        oder
        im Rollstuhl - bei "Höhenmeter für Menschen" ist jeder willkommen, der sich für unsere Benefizpartner engagieren
        möchte.
        Strecke und Spenden: Die Strecke umfasst 1.7 km und 50 Höhenmeter. Für jede Runde, die du absolvierst, sammelt
        du
        Spenden von euren großzügigen Sponsoren. Jede Runde zählt und macht einen Unterschied!
        Verpflegung am Start: Damit du bestens gestärkt in den Event starten kannst, bieten wir euch Verpflegung am
        Startpunkt an. Tankt Energie und genießt die Gemeinschaft!
        Fragen? Bei Fragen stehen wir gerne unter <a href="mailto:members@rt25.ch">members@rt25.ch</a> zur Verfügung.

        <x-page-subtitle>
            Anmeldeformular
        </x-page-subtitle>
        Es freut uns, dass du als Sportler:in bei uns mitmachen möchtest. Bitte fülle das Formular aus, damit wir alle
        nötigen Informationen zu dir haben.
        @livewire('become-athlete-form')
    </div>
@endsection
