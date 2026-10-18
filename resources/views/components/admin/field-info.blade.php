@props(['label', 'text' => null, 'icon' => 'information-circle'])

<flux:tooltip toggleable>
    <flux:button
        type="button"
        size="xs"
        variant="ghost"
        :icon="$icon"
        icon:variant="outline"
        square
        aria-label="Hinweis zu {{ $label }}"
        {{ $attributes }}
    />
    <flux:tooltip.content class="w-max max-w-[min(20rem,calc(100vw-2rem))] space-y-2">
        {{ $text ?? $slot }}</flux:tooltip.content>
</flux:tooltip>
