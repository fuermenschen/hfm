@props(['selectedCount' => 0])

<div class="flex flex-wrap items-center gap-2 sm:justify-end">
    <flux:text class="text-sm whitespace-nowrap">Ausgewählt: {{ $selectedCount }}</flux:text>

    <flux:text
        wire:loading.delay.short
        wire:target.except="clearSelection"
        class="text-xs whitespace-nowrap text-zinc-500"
    >
        Tabelle wird aktualisiert...
    </flux:text>

    <flux:button
        size="sm"
        wire:click="clearSelection"
        wire:target="clearSelection"
        wire:loading.attr="disabled"
        :disabled="$selectedCount === 0"
    >
        <span wire:loading.remove wire:target="clearSelection">Auswahl entfernen</span>
        <span wire:loading wire:target="clearSelection">Auswahl wird entfernt...</span>
    </flux:button>
</div>
