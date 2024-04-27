@extends('layouts.public')

@section('content')
    <div>
        @component('components.page-title')
            Spender:in werden
        @endcomponent
        Wir laden dich herzlich ein, Teil von "Höhenmeter für Menschen" zu werden. Du kannst deine gewünschte
        Sportler:in mit einem Geldbetrag pro Runde unterstützen. Die Sportlerin kann zwischen drei Benefizpartnern
        wählen: Brühlgut Stiftung, Kinderseele Schweiz, die Dargebotene Hand. Welche Wahl deine Sportler:in getroffen
        hat, siehst du im Anmeldeformular.

        <p></p>
        Deine Spende wird zu 100% an die jeweiligen Benefizpartner weitergegeben. Der Round Table 25 Winterthur wird die
        Abwicklung durchführen. Du erhältst eine Rechnung mit einem Spendenausweis nach der Veranstaltung am 21.9.2024.

        <x-page-subtitle>
            Anmeldeformular
        </x-page-subtitle>
        Es freut uns, dass du Spender:in werden möchtest. Indem du das Formular ausfüllst, hilfst du den Sportler:innen,
        Spenden für unsere Benefizpartner:innen zu sammeln.

        @livewire('become-donator-form')
    </div>
@endsection
