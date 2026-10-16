@component('layouts.admin', ['title' => $greeting.Auth::user()->name])
    @section('content')
        <form method="GET" action="{{ route('admin.dashboard') }}" class="flex justify-end">
            <flux:field class="w-full sm:w-80">
                <flux:label>Anlass</flux:label>
                <flux:select name="anlass" onchange="this.form.submit()">
                    <option value="" @selected($selectedEventSlug === null)>Alle Anlässe</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->slug }}" @selected($selectedEventSlug === $event->slug)>
                            {{ $event->title }} ({{ $event->slug }}){{ $event->is_published ? '' : ' - NICHT VERÖFFENTLICHT' }}
                        </option>
                    @endforeach
                </flux:select>
            </flux:field>
        </form>

        <livewire:admin-dashboard-content :event-slug="$selectedEventSlug" defer />
    @endsection
@endcomponent
