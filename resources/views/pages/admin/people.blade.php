@component('layouts.admin', ['title' => $title])

    @section('content')

        @livewire('admin-person-table', ['role' => $role])

    @endsection

@endcomponent
