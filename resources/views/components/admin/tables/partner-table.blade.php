<div>
    <x-datatable>
        <x-slot:toolbar>
            <x-datatable.toolbar-grid>
                <x-slot:topLeft>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Partner:innen suchen..." icon="magnifying-glass" />
                </x-slot:topLeft>

                <x-slot:topRight>
                    <x-datatable.selection-toolbar :selected-count="$this->selectedCount()" />
                </x-slot:topRight>

                <x-slot:bottomLeft>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($this->canCreateRows())
                            <flux:button variant="ghost" size="sm" icon="plus" wire:click="openCreate">Neu</flux:button>
                        @endif
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
                    @if ($this->canEditRows())
                        <flux:table.column class="w-1 whitespace-nowrap text-right">Aktion</flux:table.column>
                    @endif
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
                    @forelse ($partners as $row)
                        <flux:table.row wire:key="partner-{{ $row->id }}" wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}">
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
                                    @if ($columnDefinition['tooltip'])
                                        <flux:tooltip content="{{ $value }}">
                                            <span class="block max-w-60 truncate">{{ $this->truncateText($value, (int) ($columnDefinition['truncate'] ?? 48)) }}</span>
                                        </flux:tooltip>
                                    @else
                                        {{ $value }}
                                    @endif
                                </flux:table.cell>
                            @endforeach
                            @if ($this->canEditRows())
                                <flux:table.cell class="w-1 whitespace-nowrap">
                                    <div class="flex justify-end gap-1">
                                        <flux:button size="xs" variant="ghost" icon="pencil-square" square tooltip="Bearbeiten" wire:click="openEdit({{ $row->id }})" />
                                        @if ($this->canDeleteRows())
                                            <flux:button size="xs" variant="danger" icon="trash" square tooltip="Löschen" wire:click="confirmDeleteRow({{ $row->id }})" />
                                        @endif
                                    </div>
                                </flux:table.cell>
                            @endif
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
                                        <flux:text>Keine Partner:innen vorhanden.</flux:text>
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

            <flux:pagination :paginator="$partners" />
        </x-slot:footer>
    </x-datatable>

    <flux:modal name="{{ $this->createModalName() }}" wire:model.self="createModalOpen" class="md:w-xl" wire:close="cancelCreate">
        <form wire:submit="saveCreate" class="space-y-6">
            <div>
                <flux:heading size="lg">Partner:in erstellen</flux:heading>
                <flux:text class="mt-2">Neuen Partner:innen-Eintrag speichern.</flux:text>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:input label="Name" wire:model="createForm.name" />
                    <flux:error name="createForm.name" />
                </flux:field>

                <x-admin.file-select directory="partners" extensions="svg,png,jpg,jpeg,webp" recursive label="Logo hell" help="Dieses Logo wird auf der öffentlichen Startseite in der hellen Darstellung verwendet. Für jede Partnerorganisation werden eine helle und eine dunkle Variante benötigt." wire:model="createForm.logo_light_filename" :selected="$createForm['logo_light_filename'] ?? null" />

                <x-admin.file-select directory="partners" extensions="svg,png,jpg,jpeg,webp" recursive label="Logo dunkel" help="Dieses Logo wird auf der öffentlichen Startseite in der dunklen Darstellung verwendet. Für jede Partnerorganisation werden eine helle und eine dunkle Variante benötigt." wire:model="createForm.logo_dark_filename" :selected="$createForm['logo_dark_filename'] ?? null" />

                <flux:field class="sm:col-span-2">
                    <div class="flex items-center gap-1">
                        <flux:label>Kurztext</flux:label>
                        <x-admin.field-info label="Kurztext" text="Dieser allgemeine Kurztext beschreibt die begünstigte Organisation. Er erscheint auf der öffentlichen Startseite und gilt unabhängig vom Anlass." />
                    </div>
                    <flux:textarea wire:model="createForm.beneficiary_blurb" />
                    <flux:error name="createForm.beneficiary_blurb" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <div class="flex items-center gap-1">
                        <flux:label>URL</flux:label>
                        <x-admin.field-info label="URL" text="Diese Adresse wird auf der öffentlichen Startseite mit der Partnerorganisation und ihrem Logo verlinkt." />
                    </div>
                    <flux:input wire:model="createForm.url" />
                    <flux:error name="createForm.url" />
                </flux:field>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button type="button" variant="ghost" wire:click="cancelCreate">Abbrechen</flux:button>
                <flux:button type="submit" variant="primary" wire:target="saveCreate" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveCreate">Speichern</span>
                    <span wire:loading wire:target="saveCreate">Speichert...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="{{ $this->editModalName() }}" wire:model.self="editModalOpen" class="md:w-xl" wire:close="cancelEdit">
        <form wire:submit="saveEdit" class="space-y-6">
            <div>
                <flux:heading size="lg">Partner:in bearbeiten</flux:heading>
                <flux:text class="mt-2">Änderungen am Partner:innen-Eintrag speichern.</flux:text>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:input label="Name" wire:model="editForm.name" />
                    <flux:error name="editForm.name" />
                </flux:field>

                <x-admin.file-select directory="partners" extensions="svg,png,jpg,jpeg,webp" recursive label="Logo hell" help="Dieses Logo wird auf der öffentlichen Startseite in der hellen Darstellung verwendet. Für jede Partnerorganisation werden eine helle und eine dunkle Variante benötigt." wire:model="editForm.logo_light_filename" :selected="$editForm['logo_light_filename'] ?? null" />

                <x-admin.file-select directory="partners" extensions="svg,png,jpg,jpeg,webp" recursive label="Logo dunkel" help="Dieses Logo wird auf der öffentlichen Startseite in der dunklen Darstellung verwendet. Für jede Partnerorganisation werden eine helle und eine dunkle Variante benötigt." wire:model="editForm.logo_dark_filename" :selected="$editForm['logo_dark_filename'] ?? null" />

                <flux:field class="sm:col-span-2">
                    <div class="flex items-center gap-1">
                        <flux:label>Kurztext</flux:label>
                        <x-admin.field-info label="Kurztext" text="Dieser allgemeine Kurztext beschreibt die begünstigte Organisation. Er erscheint auf der öffentlichen Startseite und gilt unabhängig vom Anlass." />
                    </div>
                    <flux:textarea wire:model="editForm.beneficiary_blurb" />
                    <flux:error name="editForm.beneficiary_blurb" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <div class="flex items-center gap-1">
                        <flux:label>URL</flux:label>
                        <x-admin.field-info label="URL" text="Diese Adresse wird auf der öffentlichen Startseite mit der Partnerorganisation und ihrem Logo verlinkt." />
                    </div>
                    <flux:input wire:model="editForm.url" />
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

    <flux:modal name="{{ $this->deleteModalName() }}" class="min-w-[22rem]" wire:close="cancelDeleteRow">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Partner:in löschen?</flux:heading>
                <flux:text class="mt-2">{{ $deletingLabel }} wird gelöscht. Diese Aktion kann nicht rückgängig gemacht werden.</flux:text>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button type="button" variant="ghost" wire:click="cancelDeleteRow">Abbrechen</flux:button>
                <flux:button type="button" variant="danger" wire:click="deleteRow" wire:target="deleteRow" wire:loading.attr="disabled">Löschen</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
