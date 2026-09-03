@component('layouts.admin', ['title' => $title])
    @section('content')
        @livewire('admin-person-table', ['role' => $role])
        @if ($role === 'donor')
            @livewire('admin-payment-status-summary')
        @endif

    @endsection

@endcomponent
