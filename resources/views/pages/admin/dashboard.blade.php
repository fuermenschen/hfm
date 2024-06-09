@php

    // imports
    use App\Models\Athlete;
    use App\Models\Donator;
    use App\Models\Donation;


    // make greeting based on time
    $greeting = "Hallo ";
    $time = date('H');

    if ($time >= 12 && $time < 17) {
        $greeting = "Grüezi ";
    } elseif ($time >= 17 && $time <= 24) {
        $greeting = "Guten Abend ";
    } elseif ($time >= 4 && $time < 12) {
        $greeting = "Guten Morgen ";
    }
@endphp


@component('layouts.admin', ['title' => $greeting . Auth::user()->name])

    @section('content')

        <x-stats title="Übersicht">
            <x-admin.stat-card
                title="Anzahl Sportler:innen"
                :value="Athlete::count()"
                route="admin.athletes.index"
            />
            <x-admin.stat-card
                title="Anzahl Spender:innen"
                :value="Donator::count()"
                route="admin.athletes.index"
            />
            <x-admin.stat-card
                title="Anzahl Spenden"
                :value="Donation::count()"
                route="admin.athletes.index"
            />
        </x-stats>

    @endsection

@endcomponent
