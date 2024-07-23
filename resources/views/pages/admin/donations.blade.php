@php

    use App\Models\Athlete;
    use App\Models\SportType;

@endphp

@component('layouts.admin', ['title' => "Spenden"])

    @section('content')

        @livewire('admin-donation-table')

    @endsection

@endcomponent
