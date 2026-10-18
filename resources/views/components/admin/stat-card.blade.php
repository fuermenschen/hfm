@props(['title', 'value', 'route', 'routeParameters' => [], 'tooltip' => null, 'tooltipIcon' => 'information-circle'])

<flux:card class="relative h-full">
    <dt class="flex items-center gap-2">
        <flux:text class="min-w-0 flex-1 truncate">
            <a
                href="{{ route($route, $routeParameters) }}"
                wire:navigate.hover
                class="after:absolute after:inset-0"
            >{{ $title }}</a>
        </flux:text>
        @if ($tooltip)
            <x-admin.field-info :label="$title" :text="$tooltip" :icon="$tooltipIcon" class="relative z-10 shrink-0" />
        @endif
    </dt>
    <dd class="mt-1 text-3xl font-semibold tracking-tight tabular-nums">{{ $value }}</dd>
</flux:card>
