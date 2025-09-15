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
        </x-stats>

        <!-- Estimated Amounts -->
        <x-stats title="Spenden (geschätzt)">
            <x-admin.stat-card
                title="Erwartete Spenden"
                :value="'Fr. '.round($expectedDonationAmount, 2)"
                route="admin.donations.index"
            />
            @foreach ($partners as $partner)
                <x-admin.stat-card
                    title="{{ $partner->name }}"
                    :value="'Fr. '.round($estimatedAmounts[$partner->id], 2)"
                    route="admin.donations.index"
                />
            @endforeach
        </x-stats>

        <!-- Actual Amounts -->
        <x-stats title="Spenden (tatsächlich)">
            <x-admin.stat-card
                title="Tatsächliche Spenden"
                :value="'Fr. '.round($actualTotalAmount, 2)"
                route="admin.donations.index"
            />
            @foreach ($partners as $partner)
                <x-admin.stat-card
                    title="{{ $partner->name }}"
                    :value="'Fr. '.round($actualAmounts[$partner->id], 2)"
                    route="admin.donations.index"
                />
            @endforeach
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

        <!-- Recent Activities -->
        <x-admin.activity-list title="Letzte Aktivitäten" :activities="$mostRecentActivities" />

    @endsection

@endcomponent
