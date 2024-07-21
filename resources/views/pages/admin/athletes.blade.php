@php

    use App\Models\Athlete;
    use App\Models\SportType;

    $athletes = Athlete::all()->sortBy('privacy_name');

@endphp

@component('layouts.admin', ['title' => "Sportler:innen"])

    @section('content')

        @livewire('admin.athlete-table')

    @endsection

@endcomponent
