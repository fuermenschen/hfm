@props(['title', 'subtitle', 'events', 'selectedEventSlug'])

<header class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
    <div class="space-y-3">
        <div>
            <flux:heading size="xl" level="1">{{ $title }}</flux:heading>
            <flux:text class="mt-2 text-base">{{ $subtitle }}</flux:text>
        </div>

    </div>

    <x-portal.event-filter :events="$events" :selected-event-slug="$selectedEventSlug" />
</header>
