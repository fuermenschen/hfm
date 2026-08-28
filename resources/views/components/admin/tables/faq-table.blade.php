<div>
    <x-datatable>
        <x-slot:toolbar>
            <x-datatable.toolbar-grid>
                <x-slot:topLeft>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="FAQs suchen..."
                        icon="magnifying-glass"
                    />
                </x-slot:topLeft>

                <x-slot:topRight>
                    <x-datatable.selection-toolbar :selected-count="$this->selectedCount()" />
                </x-slot:topRight>

                <x-slot:bottomLeft>
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="plus"
                            wire:click="$dispatchTo('admin-faq-editor', 'open-faq-editor', { faqId: null })"
                        >Neu</flux:button>
                        <x-datatable.export-dropdown />
                        <x-datatable.column-visibility-dropdown :column-options="$this->visibleColumnOptions()" />
                    </div>
                </x-slot:bottomLeft>
            </x-datatable.toolbar-grid>
        </x-slot:toolbar>

        <flux:checkbox.group wire:model.live="checkboxValues">
            @php($visibleColumns = $this->visibleColumnDefinitions())
            <flux:table class="min-w-max">
                <flux:table.columns>
                    <flux:table.column>
                        <flux:field variant="inline">
                            <flux:checkbox.all />
                        </flux:field>
                    </flux:table.column>
                    @foreach ($visibleColumns as $columnKey => $columnDefinition)
                        @php($headerAlignClass = ($columnDefinition['align'] ?? 'left') === 'right' ? 'text-right' : (($columnDefinition['align'] ?? 'left') === 'center' ? 'text-center' : 'text-left'))
                        @php($headerClass = trim(($columnDefinition['width'] ?? '').' '.$headerAlignClass))
                        <flux:table.column class="{{ $headerClass }}">
                            @if ($columnDefinition['sortable'])
                                @include('components.datatable.sortable-header', ['column' => $columnKey, 'label' => $columnDefinition['label']])
                            @else
                                <span>{{ $columnDefinition['label'] }}</span>
                            @endif
                        </flux:table.column>
                    @endforeach
                    <flux:table.column class="w-1 text-right whitespace-nowrap">Aktion</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    <flux:table.row wire:loading.delay.short wire:target="{{ $this->tableLoadingTargets() }}">
                        <flux:table.cell colspan="99" class="text-center">
                            <div class="flex items-center justify-center gap-2 py-4 text-sm">
                                <flux:icon.arrow-path class="size-4 animate-spin" />
                                Tabelle wird aktualisiert...
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                    @forelse ($faqs as $row)
                        <flux:table.row
                            wire:key="faq-{{ $row->id }}"
                            wire:loading.remove
                            wire:target="{{ $this->tableLoadingTargets() }}"
                        >
                            <flux:table.cell>
                                <flux:field variant="inline">
                                    <flux:checkbox value="{{ $row->id }}" />
                                </flux:field>
                            </flux:table.cell>
                            @foreach ($visibleColumns as $columnKey => $columnDefinition)
                                @php($cellAlignClass = ($columnDefinition['align'] ?? 'left') === 'right' ? 'text-right' : (($columnDefinition['align'] ?? 'left') === 'center' ? 'text-center' : 'text-left'))
                                @php($cellClass = trim(($columnDefinition['width'] ?? '').' '.$cellAlignClass))
                                @php($value = $this->displayValue($row, $columnKey))
                                <flux:table.cell class="{{ $cellClass }}">
                                    @if ($columnKey === 'events')
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($this->linkedEvents($row) as $event)
                                                <flux:badge size="sm" color="zinc">{{ $event->slug }}</flux:badge>
                                            @endforeach
                                        </div>
                                    @elseif ($columnDefinition['tooltip'])
                                        <flux:tooltip content="{{ $value }}">
                                            <span class="block max-w-60 truncate">{{ $this->truncateText($value, (int) ($columnDefinition['truncate'] ?? 48)) }}</span>
                                        </flux:tooltip>
                                    @else
                                        {{ $value }}
                                    @endif
                                </flux:table.cell>
                            @endforeach
                            <flux:table.cell class="w-1 whitespace-nowrap">
                                <div class="flex justify-end gap-1">
                                    <flux:button
                                        size="xs"
                                        variant="ghost"
                                        icon="pencil-square"
                                        square
                                        tooltip="Bearbeiten"
                                        wire:click="$dispatchTo('admin-faq-editor', 'open-faq-editor', { faqId: {{ $row->id }} })"
                                    />
                                    @if ($this->canDeleteRows())
                                        <flux:button
                                            size="xs"
                                            variant="danger"
                                            icon="trash"
                                            square
                                            tooltip="Löschen"
                                            wire:click="confirmDeleteRow({{ $row->id }})"
                                        />
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}">
                            <flux:table.cell colspan="99" class="text-center">
                                <div class="mx-auto flex max-w-lg flex-col items-center gap-2 py-6">
                                    <flux:icon.magnifying-glass class="size-5" />
                                    @if (trim($search) !== '')
                                        <flux:text>Keine Treffer für "{{ $search }}".</flux:text>
                                        <flux:button variant="ghost" size="sm" wire:click="$set('search', '')"
                                            >Suche zurücksetzen</flux:button>
                                    @else
                                        <flux:text>Keine FAQs vorhanden.</flux:text>
                                    @endif
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:checkbox.group>

        <x-slot:footer>
            <x-datatable.per-page-select />

            <flux:pagination :paginator="$faqs" />
        </x-slot:footer>
    </x-datatable>

    <flux:modal name="{{ $this->deleteModalName() }}" class="min-w-[22rem]" wire:close="cancelDeleteRow">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">FAQ löschen?</flux:heading>
                <flux:text class="mt-2">{{ $deletingLabel }} wird gelöscht. Diese Aktion kann nicht rückgängig gemacht werden.</flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button type="button" variant="ghost" wire:click="cancelDeleteRow">Abbrechen</flux:button>
                <flux:button
                    type="button"
                    variant="danger"
                    wire:click="deleteRow"
                    wire:target="deleteRow"
                    wire:loading.attr="disabled"
                >Löschen</flux:button>
            </div>
        </div>
    </flux:modal>

    <livewire:admin-faq-editor @faq-saved="$refresh" />
</div>
