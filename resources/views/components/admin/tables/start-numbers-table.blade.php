<div>
    <x-datatable>
        <x-slot:toolbar>
            <x-datatable.toolbar-grid>
                <x-slot:topLeft>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Name oder Startnummer suchen..."
                        icon="magnifying-glass"
                    />
                </x-slot:topLeft>

                <x-slot:topRight>
                    <x-datatable.selection-toolbar :selected-count="$this->selectedCount()" />
                </x-slot:topRight>

                <x-slot:bottomLeft>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-datatable.export-dropdown label="Startliste" />
                        <flux:dropdown>
                            <flux:button size="sm" icon="hashtag">Nummern vergeben</flux:button>
                            <flux:popover class="w-80 space-y-3">
                                <flux:input wire:model="firstNumber" type="number" label="Erste Startnummer" />
                                <div class="space-y-2">
                                    <flux:button
                                        variant="primary"
                                        class="w-full"
                                        icon="hashtag"
                                        wire:click="assignMissing"
                                        wire:loading.attr="disabled"
                                        wire:target="assignMissing"
                                    >
                                        Nur fehlende vergeben
                                    </flux:button>
                                    <flux:button
                                        variant="subtle"
                                        class="w-full"
                                        icon="arrows-pointing-out"
                                        wire:click="confirmAssignAll"
                                        wire:loading.attr="disabled"
                                        wire:target="confirmAssignAll,assignAll"
                                    >
                                        Alle alphabetisch vergeben
                                    </flux:button>
                                    <flux:separator />
                                    <flux:button
                                        variant="ghost"
                                        class="w-full text-red-600! dark:text-red-400!"
                                        icon="x-mark"
                                        wire:click="confirmClearAll"
                                        wire:loading.attr="disabled"
                                        wire:target="confirmClearAll,clearAllNumbers"
                                    >
                                        Alle Nummern entfernen
                                    </flux:button>
                                </div>
                            </flux:popover>
                        </flux:dropdown>
                    </div>
                </x-slot:bottomLeft>

                <x-slot:bottomRight>
                    <div class="flex flex-wrap items-center gap-3">
                        <flux:text class="text-sm text-zinc-500">{{ $registrations->total() }} Anmeldungen</flux:text>
                        <flux:select
                            wire:model.live="eventSlug"
                            variant="listbox"
                            searchable
                            placeholder="Anlass wählen"
                            class="w-full sm:w-72"
                        >
                            @foreach ($events as $event)
                                <flux:select.option :value="$event->slug">
                                    {{ $event->title }} ({{ $event->slug }}){{ $event->is_published ? '' : ' - NICHT VERÖFFENTLICHT' }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </x-slot:bottomRight>
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
                    @forelse ($registrations as $row)
                        @php($rowClass = $loop->odd ? 'bg-zinc-50/60 dark:bg-zinc-800/40' : 'bg-white dark:bg-zinc-900')
                        <flux:table.row
                            wire:key="row-{{ $row->id }}"
                            class="{{ $rowClass }}"
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
                                <flux:table.cell class="{{ $cellClass }}">
                                    @if ($columnKey === 'start_number')
                                        <span class="font-mono text-base font-semibold tabular-nums">{{ $row->start_number ?? '–' }}</span>
                                    @elseif ($columnKey === 'event_state')
                                        @php($stateColor = match ($row->event_state->value) {
                                            'running' => 'blue',
                                            'finished' => 'green',
                                            default => 'zinc',
                                        })
                                        <flux:badge
                                            size="sm"
                                            color="{{ $stateColor }}"
                                        >{{ $row->event_state->label() }}</flux:badge>
                                    @elseif ($columnKey === 'name')
                                        {{ $row->externalUser->privacy_name }}
                                    @elseif ($columnKey === 'public_id')
                                        <span class="font-mono text-sm">{{ $row->externalUser->public_id_string }}</span>
                                    @elseif ($columnKey === 'rounds_done')
                                        <span class="font-semibold tabular-nums">{{ $row->rounds_done }}</span>
                                    @elseif ($columnKey === 'rounds_estimated')
                                        <span class="text-zinc-500 tabular-nums">{{ $row->rounds_estimated }}</span>
                                    @endif
                                </flux:table.cell>
                            @endforeach
                            <flux:table.cell class="w-1 whitespace-nowrap">
                                <div class="flex justify-end gap-1">
                                    <flux:button
                                        size="xs"
                                        variant="primary"
                                        icon="hashtag"
                                        square
                                        tooltip="Nächste freie Startnummer vergeben"
                                        wire:click="assignNextNumber({{ $row->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="assignNextNumber"
                                    />
                                    <flux:dropdown align="end">
                                        <flux:button
                                            variant="subtle"
                                            size="xs"
                                            icon="ellipsis-horizontal"
                                            square
                                            aria-label="Weitere Aktionen"
                                        />
                                        <flux:menu>
                                            <flux:menu.item
                                                wire:click="openNumberEditor({{ $row->id }})"
                                                icon="pencil-square"
                                            >
                                                Startnummer setzen…
                                            </flux:menu.item>
                                            <flux:menu.item
                                                wire:click="clearNumber({{ $row->id }})"
                                                icon="x-mark"
                                                :disabled="$row->start_number === null"
                                            >
                                                Startnummer entfernen
                                            </flux:menu.item>
                                            <flux:menu.item
                                                wire:click="openRoundsEditor({{ $row->id }})"
                                                icon="hashtag"
                                            >
                                                Runden setzen…
                                            </flux:menu.item>
                                            <flux:menu.group heading="Status setzen">
                                                @foreach (\App\Enums\EventState::cases() as $state)
                                                    <flux:menu.item
                                                        wire:click="setStatus({{ $row->id }}, '{{ $state->value }}')"
                                                        :disabled="$row->event_state->value === $state->value"
                                                    >
                                                        {{ $state->label() }}
                                                    </flux:menu.item>
                                                @endforeach
                                            </flux:menu.group>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}">
                            <flux:table.cell colspan="99" class="text-center text-zinc-500">
                                <div class="mx-auto flex max-w-lg flex-col items-center gap-2 py-6">
                                    <flux:icon.magnifying-glass class="size-5 text-zinc-400" />
                                    @if ($eventSlug === null || $eventSlug === '')
                                        <flux:text>Bitte oben einen Anlass auswählen.</flux:text>
                                    @elseif (trim($search) !== '')
                                        <flux:text>Keine Treffer für "{{ $search }}".</flux:text>
                                        <flux:button variant="ghost" size="sm" wire:click="$set('search', '')"
                                            >Suche zurücksetzen</flux:button>
                                    @else
                                        <flux:text>Keine Anmeldungen für diesen Anlass vorhanden.</flux:text>
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

            <flux:pagination :paginator="$registrations" />
        </x-slot:footer>
    </x-datatable>

    <flux:modal name="start-numbers-assign-all">
        <div class="space-y-4">
            <flux:heading size="lg">Alle Startnummern neu vergeben?</flux:heading>
            <flux:text>
                Alle bestehenden Startnummern werden überschrieben und alphabetisch neu vergeben, beginnend mit Nummer {{ $firstNumber }}.
                Bereits gedruckte Listen stimmen danach nicht mehr.
            </flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Abbrechen</flux:button>
                </flux:modal.close>
                <flux:button
                    variant="primary"
                    wire:click="assignAll"
                    wire:loading.attr="disabled"
                    wire:target="assignAll"
                >
                    Alle neu vergeben
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="start-numbers-confirm-clear">
        <div class="space-y-4">
            <flux:heading size="lg">Alle Startnummern entfernen?</flux:heading>
            <flux:text>
                Alle Startnummern dieses Anlasses werden entfernt. Runden und Status bleiben unverändert.
            </flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Abbrechen</flux:button>
                </flux:modal.close>
                <flux:button
                    variant="danger"
                    wire:click="clearAllNumbers"
                    wire:loading.attr="disabled"
                    wire:target="clearAllNumbers"
                >
                    Alle entfernen
                </flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="start-numbers-set-number">
        <form wire:submit="setNumber" class="space-y-4">
            <flux:heading size="lg">Startnummer setzen</flux:heading>
            <flux:input wire:model="numberInput" type="number" label="Startnummer" />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Abbrechen</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="setNumber">
                    Speichern
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="start-numbers-set-rounds">
        <form wire:submit="setRounds" class="space-y-4">
            <flux:heading size="lg">Runden setzen</flux:heading>
            <flux:input wire:model="roundsInput" type="number" label="Runden" />
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Abbrechen</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" wire:target="setRounds">
                    Speichern
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
