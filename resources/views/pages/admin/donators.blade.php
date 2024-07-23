@php

    use App\Models\Athlete;
    use App\Models\SportType;

@endphp

@component('layouts.admin', ['title' => "Spender:innen"])

    @section('content')

        @livewire('admin.donator-table')

    @endsection

@endcomponent
