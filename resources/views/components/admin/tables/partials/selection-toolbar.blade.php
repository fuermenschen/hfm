@props(['selectedCount' => 0])

<div class="flex flex-wrap items-center gap-2 sm:justify-end">
    <flux:text class="whitespace-nowrap text-sm">Ausgewählt: {{ $selectedCount }}</flux:text>

    <flux:button size="sm" wire:click="clearSelection" :disabled="$selectedCount === 0">
        Auswahl entfernen
    </flux:button>
</div>
