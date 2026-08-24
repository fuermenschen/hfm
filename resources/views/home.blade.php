@props(['athleteCount', 'donationCount'])

@extends('layouts.public')

@section('content')
    @if (session('no_active_event_redirected'))
        <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-600/60 dark:bg-amber-950/40 dark:text-amber-100">
            Eine Anmeldung als Sportler:in oder Spender:in ist aktuell nicht möglich, da kein aktiver Anlass
            veröffentlicht ist.
        </div>
    @endif

    @component('components.home-hero', ['currentEventPartners' => $currentEventPartners]) @endcomponent

    @if ($currentDonationEvent !== null)
        @component('components.home-content', ['athleteCount' => $athleteCount, 'donationCount' => $donationCount, 'currentEventPartners' => $currentEventPartners]) @endcomponent

        @if ($currentEventSponsors->isNotEmpty())
            @component('components.sponsors')
                @foreach ($currentEventSponsors as $sponsor)
                    @php($logoUrl = $sponsor->logoUrl())
                    @if (filled($logoUrl))
                        <x-sponsor
                            :variant="$sponsor->pivot->getAttribute('size')"
                            :logoUrl="$logoUrl"
                            :title="$sponsor->name"
                            :description="$sponsor->description"
                            :contributionText="$sponsor->pivot->getAttribute('contribution_text')"
                            :url="$sponsor->url"
                        />
                    @endif
                @endforeach

            @endcomponent
        @endif
    @endif

@endsection
