@php

    use App\Models\Athlete;
    use App\Models\SportType;

@endphp

@component('layouts.admin', ['title' => "Mitglieder"])

    @section('content')

        @livewire('admin-association-member-table')

    @endsection

@endcomponent
