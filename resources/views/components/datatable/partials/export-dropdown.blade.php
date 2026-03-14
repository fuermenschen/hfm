<flux:dropdown>
    <flux:button variant="ghost" size="sm" icon="arrow-down-tray">Export</flux:button>
    <flux:menu>
        <flux:menu.group heading="Kompletter Datensatz">
            <flux:menu.item wire:click="exportAll('xlsx')" icon="document-text">Excel</flux:menu.item>
            <flux:menu.item wire:click="exportAll('csv')" icon="document-text">CSV</flux:menu.item>
        </flux:menu.group>
        <flux:menu.group heading="Ausgewählte Zeilen">
            <flux:menu.item wire:click="exportSelected('xlsx')" icon="check-circle">Excel</flux:menu.item>
            <flux:menu.item wire:click="exportSelected('csv')" icon="check-circle">CSV</flux:menu.item>
        </flux:menu.group>
    </flux:menu>
</flux:dropdown>
