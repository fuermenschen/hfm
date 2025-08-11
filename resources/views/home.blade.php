@props(['athleteCount', 'donationCount'])

@extends('layouts.public')

@section('content')

    @component('components.home-hero') @endcomponent

    @component('components.home-content', ['athleteCount' => $athleteCount, 'donationCount' => $donationCount]) @endcomponent

    @component('components.sponsors')
    <x-sponsor
                variant="large"
                logo="rohner_spiller"
                title="Rohner Spiller"
                description="Rohner Spiller hat unsere Flyer und Poster gedruckt und unterstützt uns damit tatkräftig bei der Akquise von Sportler:innen und Spender:innen. Herzlichen Dank für die wertvolle Hilfe!"
                url="https://www.rohnerspiller.ch"
            />
            <x-sponsor
                variant="large"
                logo="tm_kommunikation"
                title="TM Kommunikation"
                description="TM Kommunikation ist unsere Kommunikationsagentur und übernimmt einen grossen Teil ihrer Arbeit für uns kostenlos. Vielen Dank für das Engagement und die professionelle Unterstützung!"
                url="https://www.tmkommunikation.ch/"
            />
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






