@extends('layouts.public')

@section('content')
    @component('components.page-title')
        Verein für Menschen
    @endcomponent

    <div class="w-full max-w-2xl mx-auto text-left sm:text-center">
        Der <strong>Verein für Menschen</strong> wurde nach der ersten Durchführung des Anlasses <strong>Höhenmeter für
            Menschen</strong> in Winterthur gegründet. Zwei der drei Gründungsmitglieder kommen aus dem Verein
        <x-inline-link href="https://rt25.ch" target="_blank"> Round Table 25 Winterthur.</x-inline-link>
    </div>

    <!-- TODO: add more content -->

@endsection
