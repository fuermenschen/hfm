@props(['events'])

<flux:select wire:model.live="eventSlug" variant="listbox" searchable clearable placeholder="Alle Anlässe" class="w-full sm:w-72">
    @foreach ($events as $event)
        <flux:select.option :value="$event->slug">
            {{ $event->title }} ({{ $event->slug }}){{ $event->is_published ? '' : ' - NICHT VERÖFFENTLICHT' }}
        </flux:select.option>
    @endforeach
</flux:select>
