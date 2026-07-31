@props(['title', 'value', 'route', 'routeParameters' => []])

<a href="{{ route($route, $routeParameters) }}" wire:navigate.hover class="block">
    <flux:card class="h-full">
        <dt><flux:text class="truncate">{{ $title }}</flux:text></dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight tabular-nums">{{ $value }}</dd>
    </flux:card>
</a>
