<div>
    <x-datatable>
        <x-slot:toolbar>
            <x-datatable.toolbar-grid>
                <x-slot:topLeft>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Sponsor:innen suchen..." icon="magnifying-glass" />
                </x-slot:topLeft>

                <x-slot:topRight>
                    <x-datatable.selection-toolbar :selected-count="$this->selectedCount()" />
                </x-slot:topRight>

                <x-slot:bottomLeft>
                    <div class="flex flex-wrap items-center gap-2">
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
                    @if ($this->canEditRows())
                        <flux:table.column class="w-1 whitespace-nowrap">Aktion</flux:table.column>
                    @endif
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
                    @forelse ($sponsors as $row)
                        <flux:table.row wire:key="sponsor-{{ $row->id }}" wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}">
                            <flux:table.cell>
                                <flux:field variant="inline">
                                    <flux:checkbox value="{{ $row->id }}" />
                                </flux:field>
                            </flux:table.cell>
                            @if ($this->canEditRows())
                                <flux:table.cell class="w-1 whitespace-nowrap">
                                    <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="openEdit({{ $row->id }})">Bearbeiten</flux:button>
                                </flux:table.cell>
                            @endif
                            @foreach ($visibleColumns as $columnKey => $columnDefinition)
                                @php($cellAlignClass = ($columnDefinition['align'] ?? 'left') === 'right' ? 'text-right' : (($columnDefinition['align'] ?? 'left') === 'center' ? 'text-center' : 'text-left'))
                                @php($cellClass = trim(($columnDefinition['width'] ?? '').' '.$cellAlignClass))
                                @php($value = $this->displayValue($row, $columnKey))
                                <flux:table.cell class="{{ $cellClass }}">
                                    @if ($columnDefinition['tooltip'])
                                        <flux:tooltip content="{{ $value }}">
                                            <span class="block max-w-60 truncate">{{ $this->truncateText($value, (int) ($columnDefinition['truncate'] ?? 48)) }}</span>
                                        </flux:tooltip>
                                    @else
                                        {{ $value }}
                                    @endif
                                </flux:table.cell>
                            @endforeach
                        </flux:table.row>
                    @empty
                        <flux:table.row wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}">
                            <flux:table.cell colspan="99" class="text-center">
                                <div class="mx-auto flex max-w-lg flex-col items-center gap-2 py-6">
                                    <flux:icon.magnifying-glass class="size-5" />
                                    @if (trim($search) !== '')
                                        <flux:text>Keine Treffer für "{{ $search }}".</flux:text>
                                        <flux:button variant="ghost" size="sm" wire:click="$set('search', '')">Suche zurücksetzen</flux:button>
                                    @else
                                        <flux:text>Keine Sponsor:innen vorhanden.</flux:text>
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

            <flux:pagination :paginator="$sponsors" />
        </x-slot:footer>
    </x-datatable>

    <flux:modal name="{{ $this->editModalName() }}" wire:model.self="editModalOpen" class="md:w-xl" wire:close="cancelEdit">
        <form wire:submit="saveEdit" class="space-y-6">
            <div>
                <flux:heading size="lg">Sponsor:in bearbeiten</flux:heading>
                <flux:text class="mt-2">Änderungen am Sponsor:innen-Eintrag speichern.</flux:text>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:input label="Name" wire:model="editForm.name" />
                    <flux:error name="editForm.name" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:textarea label="Beschreibung" wire:model="editForm.description" />
                    <flux:error name="editForm.description" />
                </flux:field>

                <x-admin.file-select directory="sponsors" extensions="svg,png,jpg,jpeg,webp" recursive label="Logo" wire:model="editForm.logo_filename" :selected="$editForm['logo_filename'] ?? null" />

                <flux:field>
                    <flux:input label="URL" wire:model="editForm.url" />
                    <flux:error name="editForm.url" />
                </flux:field>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button type="button" variant="ghost" wire:click="cancelEdit">Abbrechen</flux:button>
                <flux:button type="submit" variant="primary" wire:target="saveEdit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveEdit">Speichern</span>
                    <span wire:loading wire:target="saveEdit">Speichert...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
