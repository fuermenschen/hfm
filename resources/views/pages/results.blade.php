@extends('layouts.public')

@section('content')
    @component('components.page-title')
        Resultate 2025
    @endcomponent

    <div class="w-full max-w-2xl mx-auto text-left sm:text-center my-lg">
        Hier siehst du alle Resultate der diesjährigen Durchführung.
    </div>

    <livewire:results />

    <div class="w-full max-w-2xl mx-auto text-left sm:text-center my-lg">
        Die Angaben der Spenden basieren auf der Annahme, dass alle Spender:innen die Spendenrechnung eins zu eins begleichen. Die Erfahrung zeigt, dass sehr wenige die Rechnung nicht bezahlen und gleichzeitig einige den Rechnungsbetrag aufrunden. Deshalb stimmen die Angaben oben nicht genau mit dem überein, was wir schlussendlich an die Benefizpartner:innen überweisen. Wir werden alle Sportler:innen und Spender:innen per Mail informieren, welchen Betrag wir genau überwiesen haben.
    </div>
@endsection

