@extends('layouts.public')

@section('content')
    @component('components.page-title')
        {{ $currentDonationEvent?->contentPlainText('results.heading_md') ?? 'Resultate' }}
    @endcomponent

    <div class="w-full max-w-2xl mx-auto text-left sm:text-center my-12">
        Hier siehst du alle Resultate der diesjährigen Durchführung.
    </div>

    <livewire:results />

    <div class="w-full max-w-2xl mx-auto text-left sm:text-center my-12">
        Die Angaben der Spenden basieren auf der Annahme, dass alle Spender:innen die Spendenrechnung eins zu eins begleichen. Die Erfahrung zeigt, dass sehr wenige die Rechnung nicht bezahlen und gleichzeitig einige den Rechnungsbetrag aufrunden. Deshalb stimmen die Angaben oben nicht genau mit dem überein, was wir schlussendlich an die Benefizpartner:innen überweisen. Wir werden alle Sportler:innen und Spender:innen per Mail informieren, welchen Betrag wir genau überwiesen haben.
    </div>
@endsection
