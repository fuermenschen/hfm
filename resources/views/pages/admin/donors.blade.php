@php

    use App\Models\Athlete;
    use App\Models\SportType;

@endphp

@component('layouts.admin', ['title' => "Spender:innen"])

    @section('content')

        @livewire('admin-donor-table')
        @livewire('admin-payment-status-summary')

    @endsection

@endcomponent
