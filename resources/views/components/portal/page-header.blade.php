@props(['title', 'subtitle' => null, 'events', 'selectedEventSlug', 'showEvent' => true])

@php($selectedEvent = $events->firstWhere('slug', $selectedEventSlug))

<header>
    <div class="space-y-3">
        <div>
            <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
            @if ($subtitle)
                <flux:text class="mt-2 text-base">{{ $subtitle }}</flux:text>
            @endif
            @if ($showEvent)
                <flux:text class="mt-2 text-base">
                    {{ $selectedEvent ? $selectedEvent->title.' · '.$selectedEvent->starts_at?->format('d.m.Y') : 'Alle Anlässe' }}
                </flux:text>
            @endif
        </div>
    </div>
</header>
