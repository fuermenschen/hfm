@props(['events', 'selectedEventSlug'])

<form method="GET" action="{{ url()->current() }}" class="w-full sm:w-80">
    <flux:select name="anlass" label="Anlass" onchange="Livewire.navigate(this.form.action + '?' + new URLSearchParams(new FormData(this.form)))">
        <flux:select.option value="" :selected="$selectedEventSlug === null">Alle Anlässe</flux:select.option>

        @foreach ($events as $event)
            <flux:select.option value="{{ $event->slug }}" :selected="$selectedEventSlug === $event->slug">
                {{ $event->title }}{{ $event->starts_at ? ' · '.$event->starts_at->format('Y') : '' }}
            </flux:select.option>
        @endforeach
    </flux:select>
</form>
