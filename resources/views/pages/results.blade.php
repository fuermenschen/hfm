@extends('layouts.public')

@section('content')
    @component('components.page-title')
        Resultate 2025
    @endcomponent

    <div class="w-full max-w-2xl mx-auto text-left sm:text-center my-lg">
        Hier siehst du alle Resultate der diesjährigen Durchführung.
    </div>

    <div class="w-full max-w-2xl mx-auto my-lg">
    <flux:accordion>
        <flux:accordion.item>
            <flux:accordion.heading>Hinweis zu der Spendenberechnung</flux:accordion.heading>

            <flux:accordion.content>
                Die Angaben der Spenden basieren auf der Annahme, dass alle Spender:innen die Spendenrechnung eins zu eins begleichen. Die Erfahrung zeigt, dass sehr wenige die Rechnung nicht bezahlen und gleichzeitig einige den Rechnungsbetrag aufrunden. Deshalb stimmen die Angaben unten nicht genau mit dem überein, was wir schlussendlich an die Benefizpartner:innen überweisen.
            </flux:accordion.content>
        </flux:accordion.item>

        <flux:accordion.item>
            <flux:accordion.heading>Hinweis zu Einzelresultaten</flux:accordion.heading>

            <flux:accordion.content>
                Zu den Einzelresultaten möchten wir sagen, dass jede Runde zählt und jede Spende zählt. Wir sind kein kompetitiver Sportanlass und möchten das auch nicht sein. Wir sind ein Spendenanlass. Jede Person, die sich aufrafft und mitmacht ist ein Gewinn. Punkt.
            </flux:accordion.content>
        </flux:accordion.item>
    </flux:accordion>
    </div>

    <livewire:results />
@endsection

