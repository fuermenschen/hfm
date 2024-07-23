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

    // get the model instances
    $athletes = Athlete::with('donations')->get();
    $donators = Donator::with(['donations', 'donations.athlete'])->get();
    $donations = Donation::with('donator', 'athlete')->get();

    // sum up the stats
    $athleteCount = $athletes->count();
    $donatorCount = $donators->count();
    $donationCount = $donations->count();

    // verified stats
    $verifiedAthleteCount = $athletes->where('verified', true)->count();
    $verifiedDonationCount = $donations->where('verified', true)->count();

    // athlete specific
    $meanNumberOfDonations = $athleteCount > 0 ? $donationCount / $athleteCount : 0;
    $meanNumberOfRounds = $athletes->avg('rounds_estimated');

    // donator specific
    $meanNumberOfDonationsDonator = $donatorCount > 0 ? $donationCount / $donatorCount : 0;

    // donation specific
    $meanDonationAmount = $donationCount > 0 ? $donations->sum('amount_per_round') / $donationCount : 0;
    $expectedDonationAmount = 0;
    foreach ($athletes as $athlete) {
        $expectedDonationAmount += $athlete->rounds_estimated * $meanDonationAmount;
    }
    $minDonationAmount = $donations->sum('amount_min');


@endphp


@component('layouts.admin', ['title' => $greeting . Auth::user()->name])

    @section('content')

        <!-- Athlete -->
        <x-stats title="Sportler:innen">
            <x-admin.stat-card
                title="Registriert"
                :value="$athleteCount"
                route="admin.athletes.index"
            />
            <x-admin.stat-card
                title="Verifiziert"
                :value="$verifiedAthleteCount"
                route="admin.athletes.index"
            />
            <x-admin.stat-card
                title="Durchschn. Runden"
                :value="round($meanNumberOfRounds, 0)"
                route="admin.athletes.index"
            />
            <x-admin.stat-card
                title="Durchschn. Spenden"
                :value="round($meanNumberOfDonations, 0)"
                route="admin.athletes.index"
            />
        </x-stats>

        <!-- Donation -->
        <x-stats title="Spenden">
            <x-admin.stat-card
                title="Registriert"
                :value="$donationCount"
                route="admin.donations.index"
            />
            <x-admin.stat-card
                title="Verifiziert"
                :value="$verifiedDonationCount"
                route="admin.donations.index"
            />
            <x-admin.stat-card
                title="Durchschn. Betrag pro Runde"
                :value="'Fr. '.round($meanDonationAmount, 2)"
                route="admin.donations.index"
            />
            <x-admin.stat-card
                title="Erwartete Spenden"
                :value="'Fr. '.round($expectedDonationAmount, 2)"
                route="admin.donations.index"
            />
            <x-admin.stat-card
                title="Minimaler Spendenbetrag"
                :value="'Fr. '.round($minDonationAmount, 2)"
                route="admin.donations.index"
            />
        </x-stats>

        <!-- Donator -->
        <x-stats title="Spender:innen">
            <x-admin.stat-card
                title="Registriert"
                :value="$donatorCount"
                route="admin.donators.index"
            />
            <x-admin.stat-card
                title="Durchschn. Spenden"
                :value="round($meanNumberOfDonationsDonator, 1)"
                route="admin.donators.index"
            />
        </x-stats>

    @endsection

@endcomponent
