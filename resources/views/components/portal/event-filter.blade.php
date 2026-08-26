@props(['events', 'selectedEventSlug', 'label' => 'Anlass'])

<form method="GET" action="{{ url()->current() }}" class="w-full min-w-0">
    <flux:select
        name="anlass"
        :label="$label"
        onchange="Livewire.navigate(this.form.action + '?' + new URLSearchParams(new FormData(this.form)))"
    >
        <flux:select.option value="" :selected="$selectedEventSlug === null">Alle Anlässe</flux:select.option>

        @foreach ($events as $event)
            <flux:select.option value="{{ $event->slug }}" :selected="$selectedEventSlug === $event->slug">
                {{ $event->title }}{{ $event->starts_at ? ' · '.$event->starts_at->format('Y') : '' }}
            </flux:select.option>
        @endforeach
    </flux:select>
</form>
