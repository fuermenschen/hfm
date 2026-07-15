<div>
    <x-datatable>
        <x-slot:toolbar>
            <x-datatable.toolbar-grid>
                <x-slot:topLeft>
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Anlässe suchen..." icon="magnifying-glass" />
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
                    @forelse ($donationEvents as $donationEvent)
                        <flux:table.row wire:key="donation-event-{{ $donationEvent->id }}" wire:loading.remove wire:target="{{ $this->tableLoadingTargets() }}">
                            <flux:table.cell>
                                <flux:field variant="inline">
                                    <flux:checkbox value="{{ $donationEvent->id }}" />
                                </flux:field>
                            </flux:table.cell>
                            @foreach ($visibleColumns as $columnKey => $columnDefinition)
                                @php($cellAlignClass = ($columnDefinition['align'] ?? 'left') === 'right' ? 'text-right' : (($columnDefinition['align'] ?? 'left') === 'center' ? 'text-center' : 'text-left'))
                                @php($cellClass = trim(($columnDefinition['width'] ?? '').' '.$cellAlignClass))
                                <flux:table.cell class="{{ $cellClass }}">
                                    @switch($columnKey)
                                        @case('slug')
                                            {{ $donationEvent->slug }}
                                            @break

                                        @case('title')
                                            {{ $donationEvent->title }}
                                            @break

                                        @case('starts_at')
                                            {{ $this->formatDateTime($donationEvent->starts_at) }}
                                            @break

                                        @case('ends_at')
                                            {{ $this->formatDateTime($donationEvent->ends_at) }}
                                            @break

                                        @case('registration_opens_at')
                                            {{ $this->formatDateTime($donationEvent->registration_opens_at) }}
                                            @break

                                        @case('athlete_registration_closes_at')
                                            {{ $this->formatDateTime($donationEvent->athlete_registration_closes_at) }}
                                            @break

                                        @case('donor_registration_closes_at')
                                            {{ $this->formatDateTime($donationEvent->donor_registration_closes_at) }}
                                            @break

                                        @case('location_name')
                                            {{ $donationEvent->location_name }}
                                            @break

                                        @case('location_street')
                                            {{ $donationEvent->location_street }}
                                            @break

                                        @case('location_postal_code')
                                            {{ $donationEvent->location_postal_code }}
                                            @break

                                        @case('location_city')
                                            {{ $donationEvent->location_city }}
                                            @break

                                        @case('location_url')
                                            <flux:tooltip content="{{ $this->fallbackText($donationEvent->location_url) }}">
                                                <span class="block max-w-56 truncate">{{ $this->truncateText($donationEvent->location_url, (int) ($columnDefinition['truncate'] ?? 48)) }}</span>
                                            </flux:tooltip>
                                            @break

                                        @case('is_published')
                                            {{ $donationEvent->is_published ? 'Ja' : 'Nein' }}
                                            @break

                                        @case('created_at')
                                            {{ $this->formatDate($donationEvent->created_at) }}
                                            @break
                                    @endswitch
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
                                        <flux:text>Keine Anlässe vorhanden.</flux:text>
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

            <flux:pagination :paginator="$donationEvents" />
        </x-slot:footer>
    </x-datatable>
</div>
