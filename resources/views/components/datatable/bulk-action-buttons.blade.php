@props(['actions' => []])

<x-datatable.bulk-actions>
    @foreach ($actions as $action)
        @if (($action['type'] ?? null) !== 'wire')
            @continue
        @endif

        <flux:button
            size="sm"
            wire:click="{{ $action['click'] }}"
            wire:target="{{ $action['click'] }}"
            wire:loading.attr="disabled"
            :disabled="(bool) ($action['disabled'] ?? false)"
        >
            <span wire:loading.remove wire:target="{{ $action['click'] }}">{{ $action['label'] }}</span>
            <span
                wire:loading
                wire:target="{{ $action['click'] }}"
            >{{ $action['loading_label'] ?? $action['label'] }}</span>
        </flux:button>
    @endforeach
</x-datatable.bulk-actions>
