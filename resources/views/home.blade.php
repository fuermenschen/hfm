@props(['athleteCount', 'donationCount'])

@extends('layouts.public')

@section('content')

    @component('components.home-hero') @endcomponent

    @component('components.home-content', ['athleteCount' => $athleteCount, 'donationCount' => $donationCount]) @endcomponent

    @component('components.sponsors')
        {{--
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
        --}}

        <div class="w-4/5 sm:w-2/5">
            <div class="p-6 rounded-lg shadow-lg aspect-[2/1] mx-auto border border-slate-300/70 dark:border-slate-600/60 bg-white/70 dark:bg-slate-800/40 animate-pulse">
                <div class="h-full w-full rounded-md bg-slate-200/90 dark:bg-slate-700/70 flex items-center justify-center">
                    <div class="w-4/5 space-y-3">
                        <div class="h-3 rounded bg-slate-300 dark:bg-slate-500"></div>
                        <div class="h-3 w-2/3 mx-auto rounded bg-slate-300/90 dark:bg-slate-500/90"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-4/5 sm:w-2/5">
            <div class="p-6 rounded-lg shadow-lg aspect-[2/1] mx-auto border border-slate-300/70 dark:border-slate-600/60 bg-white/70 dark:bg-slate-800/40 animate-pulse">
                <div class="h-full w-full rounded-md bg-slate-200/90 dark:bg-slate-700/70 flex items-center justify-center">
                    <div class="w-4/5 space-y-3">
                        <div class="h-3 rounded bg-slate-300 dark:bg-slate-500"></div>
                        <div class="h-3 w-2/3 mx-auto rounded bg-slate-300/90 dark:bg-slate-500/90"></div>
                    </div>
                </div>
            </div>
        </div>

    @endcomponent

@endsection




