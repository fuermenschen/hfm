@props(['columnOptions' => []])

<flux:dropdown>
    <flux:button variant="ghost" size="sm" icon="adjustments-horizontal">Spalten</flux:button>
    <flux:menu keep-open>
        @foreach ($columnOptions as $columnKey => $columnLabel)
            <flux:menu.item keep-open wire:click="toggleColumn('{{ $columnKey }}')">
                {{ $this->isColumnVisible($columnKey) ? '✓ ' : '' }}{{ $columnLabel }}
            </flux:menu.item>
        @endforeach
    </flux:menu>
</flux:dropdown>
