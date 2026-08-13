<div>
    <x-datatable>
        <x-slot:toolbar>
            <x-datatable.toolbar-grid>
                <x-slot:topLeft>
                    <flux:input wire:model.live.debounce.300ms="search" :placeholder="$this->roleLabel().' suchen...'" icon="magnifying-glass" />
                </x-slot:topLeft>

                <x-slot:topRight>
                    <x-datatable.selection-toolbar :selected-count="$this->selectedCount()" />
                </x-slot:topRight>

                <x-slot:bottomLeft>
                    <div class="flex flex-wrap items-center gap-2">
                        <x-datatable.export-dropdown />
                        @if ($role === 'athlete')
                            <flux:dropdown>
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="document-text"
                                    wire:loading.attr="disabled"
                                    wire:target="downloadAllAthleteDocuments,downloadSelectedAthleteDocuments"
                                    :disabled="! $this->documentDownloadsEnabled()"
                                >
                                    <span wire:loading.remove wire:target="downloadAllAthleteDocuments,downloadSelectedAthleteDocuments">Dokumente</span>
                                    <span wire:loading wire:target="downloadAllAthleteDocuments,downloadSelectedAthleteDocuments">Wird erstellt...</span>
                                </flux:button>
                                <flux:menu>
                                    <flux:menu.group heading="Willkommensbrief">
                                        <flux:menu.item
                                            wire:click="downloadAllAthleteDocuments('welcome-letter')"
                                            wire:loading.attr="disabled"
                                            wire:target="downloadAllAthleteDocuments"
                                            icon="document-text"
                                            :disabled="! $this->documentDownloadsEnabled()"
                                        >
                                            Alle Sportler:innen
                                        </flux:menu.item>
                                        <flux:menu.item
                                            wire:click="downloadSelectedAthleteDocuments('welcome-letter')"
                                            wire:loading.attr="disabled"
                                            wire:target="downloadSelectedAthleteDocuments"
                                            icon="check-circle"
                                            :disabled="! $this->documentDownloadsEnabled() || $this->selectedCount() === 0"
                                        >
                                            Ausgewählte Sportler:innen
                                        </flux:menu.item>
                                    </flux:menu.group>
                                    <flux:menu.group heading="Personalisierter Flyer">
                                        <flux:menu.item
                                            wire:click="downloadAllAthleteDocuments('personalized-flyer')"
                                            wire:loading.attr="disabled"
                                            wire:target="downloadAllAthleteDocuments"
                                            icon="document-text"
                                            :disabled="! $this->documentDownloadsEnabled()"
                                        >
                                            Alle Sportler:innen
                                        </flux:menu.item>
                                        <flux:menu.item
                                            wire:click="downloadSelectedAthleteDocuments('personalized-flyer')"
                                            wire:loading.attr="disabled"
                                            wire:target="downloadSelectedAthleteDocuments"
                                            icon="check-circle"
                                            :disabled="! $this->documentDownloadsEnabled() || $this->selectedCount() === 0"
                                        >
                                            Ausgewählte Sportler:innen
                                        </flux:menu.item>
                                    </flux:menu.group>
                                </flux:menu>
                            </flux:dropdown>
                            @if (! $this->documentDownloadsEnabled())
                                <flux:callout icon="information-circle" variant="secondary" class="py-1.5">
                                    <flux:callout.text>Für Dokumente bitte genau einen Anlass auswählen.</flux:callout.text>
                                </flux:callout>
                            @endif
                            <flux:text wire:loading.flex wire:target="downloadAllAthleteDocuments,downloadSelectedAthleteDocuments" class="items-center gap-1 text-sm text-zinc-500">
                                <flux:icon.arrow-path class="size-4 animate-spin" />
                                Dokumente werden erstellt...
                            </flux:text>
                        @endif
                        <x-datatable.column-visibility-dropdown :column-options="$this->visibleColumnOptions()" />
                    </div>
                </x-slot:bottomLeft>

                <x-slot:bottomRight>
                    <div class="flex flex-wrap items-center gap-3">
                        <flux:text class="text-sm text-zinc-500">{{ $external_users->total() }} {{ $this->roleLabel() }}</flux:text>
                        <x-datatable.event-filter :events="$events" />
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
                    @if ($role === 'athlete')
                        <flux:table.column class="w-28 text-right">Dokumente</flux:table.column>
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
                    @forelse ($external_users as $row)
                        @php($rowClass = $loop->odd ? 'bg-zinc-50/60 dark:bg-zinc-800/40' : 'bg-white dark:bg-zinc-900')
                        <flux:table.row wire:key="row-{{ $row->id }}" class="{{ $rowClass }}" wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}">
                            <flux:table.cell>
                                <flux:field variant="inline">
                                    <flux:checkbox value="{{ $row->id }}" />
                                </flux:field>
                            </flux:table.cell>
                            @foreach ($visibleColumns as $columnKey => $columnDefinition)
                                @php($cellAlignClass = ($columnDefinition['align'] ?? 'left') === 'right' ? 'text-right' : (($columnDefinition['align'] ?? 'left') === 'center' ? 'text-center' : 'text-left'))
                                @php($cellClass = trim(($columnDefinition['width'] ?? '').' '.$cellAlignClass))
                                <flux:table.cell class="{{ $cellClass }}">
                                    @if ($columnKey === 'events')
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($this->linkedEvents($row) as $event)
                                                <flux:badge size="sm" color="zinc">{{ $event->slug }}</flux:badge>
                                            @endforeach
                                        </div>
                                    @elseif ($columnKey === 'partner')
                                        {{ $this->selectedAthletePartner($row) }}
                                    @else
                                        {{ $this->displayValue($row, $columnKey) }}
                                    @endif
                                </flux:table.cell>
                            @endforeach
                            @if ($role === 'athlete')
                                    <flux:table.cell class="w-28 text-right">
                                    <flux:dropdown align="end">
                                        <flux:button
                                            variant="subtle"
                                            size="xs"
                                            icon="ellipsis-horizontal"
                                            aria-label="Dokumente"
                                            wire:loading.attr="disabled"
                                            wire:target="downloadAthleteDocument"
                                            :disabled="! $this->documentDownloadsEnabled()"
                                        />
                                         <flux:menu>
                                             @if ($registration = $this->selectedAthleteRegistration($row))
                                                 <flux:menu.group heading="Story-Bilder">
                                                     <flux:menu.item href="{{ route('admin.story-image.download', [$registration, 'light']) }}" icon="arrow-down-tray">
                                                         Hell herunterladen
                                                     </flux:menu.item>
                                                     <flux:menu.item href="{{ route('admin.story-image.download', [$registration, 'dark']) }}" icon="arrow-down-tray">
                                                         Dunkel herunterladen
                                                     </flux:menu.item>
                                                 </flux:menu.group>
                                             @endif
                                             <flux:menu.item
                                                wire:click="downloadAthleteDocument({{ $row->id }}, 'welcome-letter')"
                                                wire:loading.attr="disabled"
                                                wire:target="downloadAthleteDocument"
                                                icon="document-text"
                                                :disabled="! $this->documentDownloadsEnabled()"
                                            >
                                                Willkommensbrief
                                            </flux:menu.item>
                                            <flux:menu.item
                                                wire:click="downloadAthleteDocument({{ $row->id }}, 'personalized-flyer')"
                                                wire:loading.attr="disabled"
                                                wire:target="downloadAthleteDocument"
                                                icon="document-text"
                                                :disabled="! $this->documentDownloadsEnabled()"
                                            >
                                                Personalisierter Flyer
                                            </flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            @endif
                        </flux:table.row>
                    @empty
                        <flux:table.row wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}">
                            <flux:table.cell colspan="99" class="text-center text-zinc-500">
                                <div class="mx-auto flex max-w-lg flex-col items-center gap-2 py-6">
                                    <flux:icon.magnifying-glass class="size-5 text-zinc-400" />
                                    @if (trim($search) !== '')
                                        <flux:text>Keine Treffer für "{{ $search }}".</flux:text>
                                        <flux:button variant="ghost" size="sm" wire:click="$set('search', '')">Suche zurücksetzen</flux:button>
                                    @elseif ($eventSlug !== null && $eventSlug !== '')
                                        <flux:text>Keine {{ $this->roleLabel() }} für diesen Anlass vorhanden.</flux:text>
                                    @else
                                        <flux:text>Keine {{ $this->roleLabel() }} vorhanden.</flux:text>
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

            <flux:pagination :paginator="$external_users" />
        </x-slot:footer>
    </x-datatable>
</div>
