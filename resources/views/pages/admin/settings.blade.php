@component('layouts.admin', ['title' => "Einstellungen"])

    @section('content')
        <div class="grid grid-cols-1 gap-5">
            @livewire('admin-settings')
        </div>
    @endsection

@endcomponent
