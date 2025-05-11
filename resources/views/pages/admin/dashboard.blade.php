@php

    // imports
    use App\Models\Athlete;
    use App\Models\Donator;
    use App\Models\Donation;
    use App\Models\Partner;
    use App\Models\AssociationMember;

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
    $donations = Donation::with('donator', 'athlete', 'athlete.partner')->get();
    $partners = Partner::get();
    $members = AssociationMember::get();

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

    // actual amount based on actual rounds
    $actualAmounts = array();
    $estimatedAmounts = array();

    foreach ($partners as $partner) {
        $actualAmounts[$partner->id] = 0;
        $estimatedAmounts[$partner->id] = 0;
    }

    foreach ($donations as $donation) {
        $this_partner_id = $donation->athlete->partner->id;
        $this_amount = $donation->amount_per_round * $donation->athlete->rounds_done;
        $this_estimated = $donation->amount_per_round * $donation->athlete->rounds_estimated;
        if ($donation->amount_min) {
            if ($this_amount < $donation->amount_min) {
                $this_amount = $donation->amount_min;
            }
            if ($this_estimated < $donation->amount_min) {
                $this_estimated = $donation->amount_min;
            }
        }
        if ($donation->amount_max) {
            if ($this_amount > $donation->amount_max) {
                $this_amount = $donation->amount_max;
            }
            if ($this_estimated > $donation->amount_max) {
                $this_estimated = $donation->amount_max;
            }
        }

        $actualAmounts[$this_partner_id] += $this_amount;
        $estimatedAmounts[$this_partner_id] += $this_estimated;
    }

    $actualTotalAmount = array_sum($actualAmounts);
    $expectedDonationAmount = array_sum($estimatedAmounts);

    // most recent activity
    // most recent athlete activity
    $mostRecentAthletes = $athletes->sortByDesc('created_at')->take(30);

    // most recent donator activity
    $mostRecentDonators = $donators->sortByDesc('created_at')->take(30);

    // most recent donation activity
    $mostRecentDonations = $donations->sortByDesc('created_at')->take(30);

    // most recent payments
    $mostRecentPayments = $donators->sortByDesc('invoice_paid_at')->take(30);

    // most recent  member registrations
    $mostRecentMembers = $members->sortByDesc('created_at')->take(30);

    // remove entries older than 7 days
    $sevenDaysAgo = now()->subDays(7);
    $mostRecentAthletes = $mostRecentAthletes->filter(function ($value, $key) use ($sevenDaysAgo) {
        return $value->created_at > $sevenDaysAgo;
    });
    $mostRecentDonators = $mostRecentDonators->filter(function ($value, $key) use ($sevenDaysAgo) {
        return $value->created_at > $sevenDaysAgo;
    });
    $mostRecentDonations = $mostRecentDonations->filter(function ($value, $key) use ($sevenDaysAgo) {
        return $value->created_at > $sevenDaysAgo;
    });
    $mostRecentPayments = $mostRecentPayments->filter(function ($value, $key) use ($sevenDaysAgo) {
        return $value->invoice_paid_at > $sevenDaysAgo;
    });
    $mostRecentMembers = $mostRecentMembers->filter(function ($value, $key) use ($sevenDaysAgo) {
        return $value->created_at > $sevenDaysAgo;
    });

    // create an array with the most recent activities
    $mostRecentActivities = array();
    foreach ($mostRecentAthletes as $athlete) {
        $mostRecentActivities[] = [
            'type' => 'athlete',
            'name' => $athlete->privacy_name,
            'created_at' => $athlete->created_at,
        ];
    }
    foreach ($mostRecentDonators as $donator) {
        $mostRecentActivities[] = [
            'type' => 'donator',
            'name' => $donator->privacy_name,
            'created_at' => $donator->created_at,
        ];
    }
    foreach ($mostRecentDonations as $donation) {
        $mostRecentActivities[] = [
            'type' => 'donation',
            'name' => $donation->donator->privacy_name,
            'name2' => $donation->athlete->privacy_name,
            'created_at' => $donation->created_at,
        ];
    }
    foreach ($mostRecentPayments as $payment) {
        $mostRecentActivities[] = [
            'type' => 'payment',
            'name' => $payment->privacy_name,
            'created_at' => $payment->invoice_paid_at,
        ];
    }
    foreach ($mostRecentMembers as $member) {
        $mostRecentActivities[] = [
            'type' => 'member',
            'name' => $member->first_name . ' ' . $member->last_name,
            'created_at' => $member->created_at,
        ];
    }

    // sort the array by created_at
    usort($mostRecentActivities, function ($a, $b) {
        return $a['created_at'] <=> $b['created_at'];
    });

    // get the last 10 entries
    $mostRecentActivities = array_slice($mostRecentActivities, -10);

    // reverse the array
    $mostRecentActivities = array_reverse($mostRecentActivities);

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
                title="Erwartete Spenden (geschätzte Runden)"
                :value="'Fr. '.round($expectedDonationAmount, 2)"
                route="admin.donations.index"
            />
            <x-admin.stat-card
                title="Tatsächliche Spenden (tatsächliche Runden)"
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

        <!-- Members -->
        <x-stats title="Vereinsmitglieder">
            <x-admin.stat-card
                title="Registriert"
                :value="$members->count()"
                route="admin.association-members.index"
            />
        </x-stats>

        <!-- Recent Activities -->
        <x-admin.activity-list title="Letzte Aktivitäten" :activities="$mostRecentActivities" />

    @endsection

@endcomponent
