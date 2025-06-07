@props(['athleteCount', 'donationCount'])

@extends('layouts.public')

@section('content')

    @component('components.home-hero') @endcomponent

    @component('components.home-content', ['athleteCount' => $athleteCount, 'donationCount' => $donationCount]) @endcomponent

    @component('components.sponsors')
        <x-sponsor
            variant="small"
            logo="veloplus"
            title="Veloplus"
            description="Veloplus unterstützt uns mit Gutscheinen über insgesamt Fr. 150.-, die wir an unsere Sportlker:innen abgeben können. Herzlichen Dank!"
            url="https://www.veloplus.ch"
        />
        <x-sponsor
            variant="small"
            logo="intersport_egli"
            title="Intersport Egli"
            description="Eglisport unterstützt uns mit Gutscheinen über insgesamt Fr. 300.-, die wir an unsere Sportler:innen abgeben können. Herzlichen Dank!"
            url="https://eglisport.ch/"
        />
    @endcomponent

@endsection
