@props(['label', 'text'])

<flux:tooltip :content="$text" toggleable>
    <flux:button type="button" size="xs" variant="ghost" icon="information-circle" icon:variant="outline" square aria-label="Hinweis zu {{ $label }}" />
</flux:tooltip>
